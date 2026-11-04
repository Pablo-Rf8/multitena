<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialGrant extends Model
{
    protected $fillable = ['credential_id', 'user_id', 'level', 'can_reveal'];

    protected function casts(): array
    {
        return ['can_reveal' => 'boolean'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
