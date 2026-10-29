<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileSession extends Model
{
    protected $fillable = ['company_id', 'user_id', 'device_id', 'token_id', 'started_at', 'ended_at', 'expires_at', 'status', 'end_reason'];

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    protected function casts(): array
    {
        return ['started_at' => 'datetime', 'ended_at' => 'datetime', 'expires_at' => 'datetime'];
    }
}
