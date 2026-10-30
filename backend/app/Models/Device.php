<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = ['company_id', 'user_id', 'identifier_hash', 'name', 'status', 'last_seen_at', 'push_token'];

    protected function casts(): array
    {
        return ['last_seen_at' => 'datetime', 'push_token' => 'encrypted'];
    }
}
