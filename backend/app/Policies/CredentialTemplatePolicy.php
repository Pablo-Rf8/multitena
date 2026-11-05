<?php

namespace App\Policies;

use App\Models\CredentialTemplate;
use App\Models\User;

class CredentialTemplatePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->company_id !== null && in_array($user->role, ['company_admin', 'branch_admin', 'employee'], true);
    }

    public function view(User $user, CredentialTemplate $template): bool
    {
        if (! $this->viewAny($user) || $user->company_id !== $template->company_id) {
            return false;
        }

        return in_array($user->role, ['company_admin', 'branch_admin'], true)
            || $template->shares()->where('user_id', $user->id)->available()->exists();
    }

    public function create(User $user): bool
    {
        return $user->company_id !== null && $user->role === 'company_admin';
    }

    public function update(User $user, CredentialTemplate $template): bool
    {
        return $this->create($user) && $user->company_id === $template->company_id;
    }

    public function delete(User $user, CredentialTemplate $template): bool
    {
        return $this->update($user, $template);
    }

    public function share(User $user, CredentialTemplate $template): bool
    {
        return $this->update($user, $template);
    }
}
