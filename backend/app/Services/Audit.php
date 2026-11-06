<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;

class Audit
{
    public function record(string $event, Model $subject, ?int $companyId = null, array $properties = []): void
    {
        $preview = session('demo_preview');
        if (is_array($preview) && auth()->user()?->company_id === ($preview['company_id'] ?? null)) {
            $properties['demo_user_id'] = auth()->id();
        }
        ActivityLog::create([
            'company_id' => $companyId,
            'actor_id' => is_array($preview) && auth()->user()?->company_id === ($preview['company_id'] ?? null)
                ? ($preview['super_admin_id'] ?? auth()->id()) : auth()->id(),
            'event' => $event,
            'subject_type' => class_basename($subject),
            'subject_id' => $subject->getKey(),
            'properties' => $properties,
            'created_at' => now(),
        ]);
    }
}
