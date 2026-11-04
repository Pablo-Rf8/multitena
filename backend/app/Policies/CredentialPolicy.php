<?php

namespace App\Policies;

use App\Models\Credential;
use App\Models\User;

class CredentialPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->tenant($user);
    }

    public function create(User $user): bool
    {
        return $this->tenant($user) && in_array($user->role, ['company_admin', 'branch_admin'], true);
    }

    public function view(User $user, Credential $credential): bool
    {
        if (! $this->sameCompany($user, $credential)) {
            return false;
        }
        if ($credential->owner_id === $user->id) {
            return true;
        }
        if ($credential->visibility === 'private' && ! in_array($user->role, ['company_admin', 'branch_admin'], true)) {
            return false;
        }

        return $credential->grants()->where('user_id', $user->id)->exists();
    }

    public function update(User $user, Credential $credential): bool
    {
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
        return $this->view($user, $credential) && ($credential->owner_id === $user->id
            || $credential->grants()->where('user_id', $user->id)->where('can_reveal', true)->exists());
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
