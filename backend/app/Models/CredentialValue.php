<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CredentialValue extends Model
{
    protected $fillable = ['credential_id', 'field_id', 'value', 'secret_ciphertext'];

    protected $hidden = ['value', 'secret_ciphertext'];

    public function field(): BelongsTo
    {
        return $this->belongsTo(CredentialField::class, 'field_id');
    }

    public function credential(): BelongsTo
    {
        return $this->belongsTo(Credential::class);
    }
}
