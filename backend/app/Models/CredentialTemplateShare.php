<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialTemplateShare extends Model
{
    protected $fillable = ['company_id', 'template_id', 'user_id', 'shared_by_id', 'max_accesses', 'used_accesses', 'expires_at', 'revoked_at'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];
    }

    public function scopeAvailable(Builder $query): Builder
    {
        return $query->whereNull('revoked_at')
            ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->where(fn (Builder $q) => $q->whereNull('max_accesses')->orWhereColumn('used_accesses', '<', 'max_accesses'));
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CredentialTemplate::class, 'template_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
