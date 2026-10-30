<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'phone', 'password', 'role', 'company_id', 'branch_id', 'is_active', 'last_login_at', 'approval_status', 'reviewed_by', 'reviewed_at', 'category_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    protected $attributes = ['is_active' => true];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class, 'actor_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }

    public function mobileSessions(): HasMany
    {
        return $this->hasMany(MobileSession::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'last_login_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }
}
