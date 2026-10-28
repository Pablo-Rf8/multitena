<?php

namespace App\Services;

use App\Models\Contact;
use App\Models\Opportunity;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class CrmScope
{
    public function contacts(User $user): Builder
    {
        return Contact::query()
            ->where('company_id', $user->company_id)
            ->when($user->role === 'branch_admin', fn (Builder $query) => $query->where('branch_id', $user->branch_id));
    }

    public function opportunities(User $user): Builder
    {
        return Opportunity::query()
            ->where('company_id', $user->company_id)
            ->when($user->role === 'branch_admin', fn (Builder $query) => $query->where('branch_id', $user->branch_id));
    }
}
