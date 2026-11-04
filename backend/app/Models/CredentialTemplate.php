<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CredentialTemplate extends Model
{
    protected $fillable = ['company_id', 'name'];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function fields(): HasMany
    {
        return $this->hasMany(CredentialField::class, 'template_id')->orderBy('position');
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(Credential::class, 'template_id');
    }
}
