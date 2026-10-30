<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CodeLink extends Model
{
    protected $fillable = ['user_id', 'company_id', 'branch_id', 'issued_by', 'token_hash', 'handoff_token', 'status', 'issued_at', 'expires_at', 'suspended_at', 'revoked_at'];

    protected $hidden = ['token_hash', 'handoff_token'];

    protected function casts(): array
    {
        return ['handoff_token' => 'encrypted', 'issued_at' => 'datetime', 'expires_at' => 'datetime', 'suspended_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function isUsable(): bool
    {
        return $this->status === 'active' && now()->lt($this->expires_at);
    }
}
