<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    protected $attributes = ['is_active' => true];

    protected $fillable = ['name', 'slug', 'code', 'is_demo', 'is_active', 'contact_email', 'phone', 'currency', 'theme_primary', 'theme_secondary', 'theme_accent', 'logo_path'];

    protected function casts(): array
    {
        return ['is_demo' => 'boolean', 'is_active' => 'boolean'];
    }

    public function branches(): HasMany
    {
        return $this->hasMany(Branch::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function opportunities(): HasMany
    {
        return $this->hasMany(Opportunity::class);
    }
}
