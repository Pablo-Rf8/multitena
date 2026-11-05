<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Credential extends Model
{
    protected $fillable = ['company_id', 'template_id', 'owner_id', 'title', 'visibility'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CredentialTemplate::class, 'template_id');
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CredentialValue::class);
    }

    public function grants(): HasMany
    {
        return $this->hasMany(CredentialGrant::class);
    }

    public function shares(): HasMany
    {
        return $this->hasMany(CredentialShare::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(CredentialFavorite::class);
    }

    public function accesses(): HasMany
    {
        return $this->hasMany(CredentialAccess::class);
    }
}
