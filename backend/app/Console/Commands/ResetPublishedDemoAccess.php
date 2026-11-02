<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Models\MobileSession;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ResetPublishedDemoAccess extends Command
{
    protected $signature = 'app:reset-published-demo-access';

    protected $description = 'Actualiza cuentas DEMO publicadas usando claves temporales del entorno';

    public function handle(CreateCompany $creator): int
    {
        $raw = getenv('DEMO_ACCESS_RESET_JSON');
        if (! is_string($raw) || $raw === '') return self::SUCCESS;

        $passwords = json_decode($raw, true);
        $expected = ['superadmin@multitena.local', 'admin@pepsi-demo.test', 'admin@coca-cola-demo.test'];
        if (! is_array($passwords) || array_keys($passwords) !== $expected ||
            collect($passwords)->contains(fn ($password) => ! is_string($password) || strlen($password) < 16)) {
            $this->error('Configuración de acceso DEMO inválida.');
            return self::FAILURE;
        }

        DB::transaction(function () use ($creator, $passwords) {
            $super = User::where('email', 'superadmin@multitena.local')->where('role', 'super_admin')->first();
            if ($super) $this->resetPassword($super, $passwords[$super->email]);
            else $this->warn('No se encontró el superadministrador esperado.');

            foreach (['pepsi-demo' => ['Pepsi (DEMO)', 'admin@pepsi-demo.test'], 'coca-cola-demo' => ['Coca-Cola (DEMO)', 'admin@coca-cola-demo.test']] as $slug => [$name, $email]) {
                $company = Company::where('slug', $slug)->first();
                if (! $company) {
                    $company = $creator->handle($name, $email, $passwords[$email], true);
                    $company->update($slug === 'pepsi-demo'
                        ? ['theme_primary' => '#0754a6', 'theme_secondary' => '#102d5a', 'theme_accent' => '#d22739', 'logo_mode' => 'demo']
                        : ['theme_primary' => '#bd1930', 'theme_secondary' => '#711323', 'theme_accent' => '#f5d6d9', 'logo_mode' => 'demo']);
                    $this->info($name.' creada; código: '.$company->code);
                    continue;
                }
                if (! $company->is_demo && $company->name !== $name) {
                    $this->warn($name.' no coincide con la empresa DEMO esperada; no se modificó.');
                    continue;
                }
                if (! $company->is_demo) $company->update(['is_demo' => true]);
                $user = $company->users()->where('email', $email)->where('role', 'company_admin')->first();
                if (! $user) {
                    $branch = $company->branches()->where('is_central', true)->first();
                    if (! $branch) {
                        $this->warn($name.' no tiene sucursal Central; no se modificó.');
                        continue;
                    }
                    $user = $company->users()->create(['branch_id' => $branch->id, 'name' => 'Administrador de '.$name, 'email' => $email, 'password' => $passwords[$email], 'role' => 'company_admin']);
                }
                $this->resetPassword($user, $passwords[$email]);
                $this->info($name.' lista; código: '.$company->code);
            }
        });

        return self::SUCCESS;
    }

    private function resetPassword(User $user, string $password): void
    {
        if (Hash::check($password, $user->password)) return;

        $user->update(['password' => $password]);
        DB::table('sessions')->where('user_id', $user->id)->delete();
        MobileSession::where('user_id', $user->id)->where('status', 'active')->update([
            'status' => 'revoked', 'ended_at' => now(), 'end_reason' => 'password_reset',
        ]);
        $user->tokens()->delete();
        $this->info('Clave actualizada para '.$user->email.'.');
    }
}
