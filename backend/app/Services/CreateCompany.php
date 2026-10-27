<?php

namespace App\Services;

use App\Models\Company;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateCompany
{
    public function handle(string $name, ?string $adminEmail, string $password, bool $isDemo = false): Company
    {
        $name = trim($name);
        $slug = Str::slug($name);
        $email = strtolower(trim($adminEmail ?: 'admin@'.$slug.'.com'));

        if ($slug === '') {
            throw ValidationException::withMessages(['name' => 'Ingresá un nombre válido.']);
        }

        if (Company::where('slug', $slug)->exists()) {
            throw ValidationException::withMessages(['name' => 'Ya existe una empresa con ese nombre.']);
        }

        if (User::where('email', $email)->exists()) {
            throw ValidationException::withMessages(['admin_email' => 'Ese correo ya pertenece a un usuario.']);
        }

        return DB::transaction(function () use ($name, $slug, $email, $password, $isDemo) {
            $company = Company::create(['name' => $name, 'slug' => $slug, 'is_demo' => $isDemo]);
            $branch = $company->branches()->create(['name' => 'Central', 'is_central' => true]);
            $company->users()->create([
                'branch_id' => $branch->id,
                'name' => 'Administrador de '.$name,
                'email' => $email,
                'password' => $password,
                'role' => 'company_admin',
            ]);

            return $company;
        });
    }
}
