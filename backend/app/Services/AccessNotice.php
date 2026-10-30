<?php

namespace App\Services;

use App\Jobs\SendAccessPush;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AccessNotice
{
    public static function admins(int $companyId, string $kind, int $subjectId, string $title): void
    {
        foreach (User::where('company_id', $companyId)->where('role', 'company_admin')->where('is_active', true)->get() as $admin) {
            self::user($admin, $kind, $subjectId, $title);
        }
    }

    public static function user(User $recipient, string $kind, int $subjectId, string $title): void
    {
        $id = DB::table('access_notifications')->insertGetId([
            'company_id' => $recipient->company_id, 'recipient_id' => $recipient->id,
            'kind' => $kind, 'subject_id' => $subjectId, 'title' => $title,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        if (config('services.fcm.project_id') && config('services.fcm.credentials_file')) {
            SendAccessPush::dispatch($id)->afterCommit();
        }
    }
}
