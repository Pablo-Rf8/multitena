<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CredentialField extends Model
{
    protected $fillable = ['template_id', 'name', 'type', 'position', 'required'];

    protected function casts(): array
    {
        return ['required' => 'boolean'];
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(CredentialTemplate::class, 'template_id');
    }

    public function values(): HasMany
    {
        return $this->hasMany(CredentialValue::class, 'field_id');
    }
}
