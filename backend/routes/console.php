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

Artisan::command('app:expire-web-sessions', function () {
    $count = MobileSession::where('channel', 'web')->where('status', 'active')->where('expires_at', '<=', now())->update(['status' => 'expired', 'end_reason' => 'expired', 'ended_at' => now()]);
    $this->info("$count sesiones web expiradas.");
})->purpose('Marca las sesiones web expiradas');
Schedule::command('app:expire-web-sessions')->hourly();

Artisan::command('app:backup-sqlite', function () {
    $source = config('database.connections.sqlite.database');
    if (! is_string($source) || ! is_file($source)) {
        $this->error('No se encontró la base SQLite.');
        return 1;
    }
    $directory = env('BACKUP_DIRECTORY', base_path('../bd/backups'));
    if (! is_dir($directory) && ! mkdir($directory, 0700, true) && ! is_dir($directory)) throw new RuntimeException('No se pudo crear la carpeta de respaldos.');
    $destination = $directory.'/multitena-'.now()->format('Ymd-His').'-'.bin2hex(random_bytes(3)).'.sqlite';
    $pdo = new PDO('sqlite:'.$source);
    $pdo->exec('VACUUM INTO '.$pdo->quote($destination));
    @chmod($destination, 0600);
    $this->info('Respaldo creado: '.$destination);
    return 0;
})->purpose('Crea un respaldo consistente de SQLite sin detener la aplicación');
Schedule::command('app:backup-sqlite')->dailyAt('02:00')->withoutOverlapping();
