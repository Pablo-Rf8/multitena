<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    public function record(string $event, Model $subject, ?int $companyId = null, array $properties = []): void
    {
        ActivityLog::create([
            'company_id' => $companyId,
            'actor_id' => auth()->id(),
            'event' => $event,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }
}
