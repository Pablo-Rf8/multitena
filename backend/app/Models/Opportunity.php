<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Opportunity extends Model
{
    protected $fillable = ['company_id', 'branch_id', 'contact_id', 'title', 'amount_cents', 'stage', 'expected_close_on', 'description', 'is_demo'];

    protected function casts(): array
    {
        return ['expected_close_on' => 'date', 'is_demo' => 'boolean'];
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }
}
