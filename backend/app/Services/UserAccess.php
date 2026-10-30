<?php

namespace App\Services;

use App\Models\CompanySetting;
use App\Models\User;

class UserAccess
{
    public function allowed(User $user): bool
    {
        return $user->is_active
            && $user->company?->is_active
            && $user->branch?->is_active
            && $user->branch?->company_id === $user->company_id
            && ($user->role !== 'employee' || (
                $user->approval_status === 'approved'
                && CompanySetting::firstOrCreate(['company_id' => $user->company_id])->employee_access_enabled
            ));
    }
}
