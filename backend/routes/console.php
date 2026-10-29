<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use App\Models\MobileSession;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('app:expire-mobile-sessions', function () {
    $count = MobileSession::where('status', 'active')->where('expires_at', '<=', now())
        ->update(['status' => 'expired', 'end_reason' => 'expired', 'ended_at' => now()]);
    $this->info("$count sesiones expiradas actualizadas.");
})->purpose('Marca las sesiones móviles expiradas');

Schedule::command('app:expire-mobile-sessions')->hourly();
