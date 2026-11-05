<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialAccess extends Model
{
    public $timestamps = false;

    protected $fillable = ['company_id', 'credential_id', 'share_id', 'user_id', 'opened_at'];

    protected function casts(): array
    {
        return ['opened_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function share(): BelongsTo
    {
        return $this->belongsTo(CredentialShare::class, 'share_id');
    }
}
