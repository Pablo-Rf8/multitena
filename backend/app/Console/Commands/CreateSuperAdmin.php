<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

class CreateSuperAdmin extends Command
{
    protected $signature = 'app:create-super-admin';

    protected $description = 'Crea el primer superadministrador sin datos de prueba';

    public function handle(): int
    {
        $name = text('Nombre', required: true);
        $email = strtolower(text('Correo electrónico', required: true));
        $secret = password('Contraseña (mínimo 12 caracteres)', required: true);

        if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->error('Ingresá un correo válido.');

            return self::FAILURE;
        }

        if (strlen($secret) < 12) {
            $this->error('La contraseña debe tener al menos 12 caracteres.');

            return self::FAILURE;
        }

        if (User::where('email', $email)->exists()) {
            $this->error('Ese correo ya pertenece a un usuario.');

            return self::FAILURE;
        }

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($secret),
            'role' => 'super_admin',
        ]);

        $this->info('Superadministrador creado.');

        return self::SUCCESS;
    }
}
