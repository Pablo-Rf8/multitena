<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Device extends Model
{
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected $fillable = ['company_id', 'user_id', 'identifier_hash', 'name', 'status', 'last_seen_at', 'push_token'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'push_token' => 'encrypted'];
    }
}
