<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class BootstrapAdmin extends Command
{
    protected $signature = 'app:bootstrap-admin';

    protected $description = 'Crea el primer superadministrador desde variables de entorno seguras';

    public function handle(): int
    {
        if (User::where('role', 'super_admin')->exists()) return self::SUCCESS;

        $email = strtolower(trim((string) env('BOOTSTRAP_ADMIN_EMAIL')));
        $secret = (string) env('BOOTSTRAP_ADMIN_PASSWORD');
        if ($email === '' && $secret === '') return self::SUCCESS;
        if (! filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($secret) < 12) {
            $this->error('La configuración inicial de superadministrador es inválida.');
            return self::FAILURE;
        }
        User::create(['name' => 'Superadministrador', 'email' => $email, 'password' => $secret, 'role' => 'super_admin']);
        $this->info('Superadministrador inicial creado.');
        return self::SUCCESS;
    }
}
