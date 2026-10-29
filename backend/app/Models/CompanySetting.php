<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanySetting extends Model
{
    protected $attributes = [
        'employee_access_enabled' => true,
        'require_device_approval' => false,
        'token_lifetime_hours' => 720,
    ];

    protected $fillable = ['company_id', 'email_domains', 'employee_access_enabled', 'require_device_approval', 'token_lifetime_hours'];

    protected function casts(): array
    {
        return ['email_domains' => 'array', 'employee_access_enabled' => 'boolean', 'require_device_approval' => 'boolean'];
    }
}
