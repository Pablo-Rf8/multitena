<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->role === 'super_admin' || $this->tenant($user);
    }

    public function create(User $user): bool
    {
        return $this->tenant($user) && in_array($user->role, ['company_admin', 'branch_admin'], true);
    }

    public function view(User $user, Credential $credential): bool
    {
        if ($user->role === 'super_admin') {
            return $this->platformShare($user, $credential);
        }
        if (! $this->sameCompany($user, $credential)) {
            return false;
        }
        if ($credential->owner_id === $user->id) {
            return true;
        }
        if ($credential->visibility === 'private' && ! in_array($user->role, ['company_admin', 'branch_admin'], true)) {
            return false;
        }

        return $credential->grants()->where('user_id', $user->id)->exists()
            || $credential->shares()->where('user_id', $user->id)->available()->exists();
    }

    public function update(User $user, Credential $credential): bool
    {
        if ($user->role === 'super_admin') {
            return false;
        }
        return $this->view($user, $credential) && ($credential->owner_id === $user->id
            || $credential->grants()->where('user_id', $user->id)->where('level', 'edit')->exists());
    }

    public function delete(User $user, Credential $credential): bool
    {
        return $this->sameCompany($user, $credential) && $credential->owner_id === $user->id;
    }

    public function share(User $user, Credential $credential): bool
    {
        return $this->delete($user, $credential);
    }

    public function reveal(User $user, Credential $credential): bool
    {
        if ($user->role === 'super_admin') {
            return $user->is_active && $credential->visibility === 'shared'
                && $credential->company?->is_active && $this->openedRevealShare($user, $credential);
        }
        if (! $this->sameCompany($user, $credential)) {
            return false;
        }
        if ($credential->owner_id === $user->id) {
            return true;
        }
        if ($credential->visibility === 'private' && ! in_array($user->role, ['company_admin', 'branch_admin'], true)) {
            return false;
        }
        if ($credential->grants()->where('user_id', $user->id)->where('can_reveal', true)->exists()) {
            return true;
        }

        return $this->openedRevealShare($user, $credential);
    }

    private function openedRevealShare(User $user, Credential $credential): bool
    {
        $ticket = session('credential_share_open.'.$credential->id);
        if (! is_array($ticket) || ($ticket['user_id'] ?? null) !== $user->id
            || ($ticket['opened_at'] ?? 0) < now()->subMinutes(5)->timestamp) {
            return false;
        }

        return $credential->shares()->whereKey($ticket['share_id'] ?? 0)
            ->where('user_id', $user->id)->where('can_reveal', true)
            ->whereNull('revoked_at')
            ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))->exists();
    }

    private function platformShare(User $user, Credential $credential): bool
    {
        return $user->is_active && $credential->visibility === 'shared'
            && $credential->company?->is_active
            && $credential->shares()->where('user_id', $user->id)->available()->exists();
    }

    private function tenant(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, ['company_admin', 'branch_admin', 'employee'], true);
    }

    private function sameCompany(User $user, Credential $credential): bool
    {
        return $this->tenant($user) && $user->company_id === $credential->company_id;
    }
}
