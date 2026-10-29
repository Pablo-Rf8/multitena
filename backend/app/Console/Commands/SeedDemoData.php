<?php

namespace App\Console\Commands;

use App\Models\Company;
use App\Services\CreateCompany;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SeedDemoData extends Command
{
    protected $signature = 'app:seed-demo';

    protected $description = 'Agrega empresas y usuarios DEMO sin cambiar datos existentes';

    public function handle(CreateCompany $creator): int
    {
        if (! app()->environment(['local', 'testing'])) {
            $this->error('Los datos DEMO solo se permiten en local o testing.');

            return self::FAILURE;
        }

        $examples = [
            ['Pepsi (DEMO)', 'admin@pepsi-demo.test', ['Norte', 'Sur']],
            ['Coca-Cola (DEMO)', 'admin@coca-cola-demo.test', ['Oeste']],
        ];

        foreach ($examples as [$name, $email, $extraBranches]) {
            if (Company::where('slug', Str::slug($name))->exists()) {
                $this->line($name.' ya existe; se conservarán sus datos.');

                continue;
            }

            $password = bin2hex(random_bytes(12));
            $branchAdminPassword = $name === 'Pepsi (DEMO)' ? bin2hex(random_bytes(12)) : null;

            DB::transaction(function () use ($creator, $name, $email, $password, $extraBranches, $branchAdminPassword) {
                $company = $creator->handle($name, $email, $password, true);
                $company->update($company->slug === 'pepsi-demo'
                    ? ['theme_primary' => '#0754a6', 'theme_secondary' => '#102d5a', 'theme_accent' => '#d22739']
                    : ['theme_primary' => '#bd1930', 'theme_secondary' => '#711323', 'theme_accent' => '#f5d6d9']);
                foreach ($extraBranches as $branchName) {
                    $branch = $company->branches()->create(['name' => $branchName, 'is_central' => false]);
                    if ($branchName === 'Norte' && $branchAdminPassword) {
                        $company->users()->create([
                            'name' => 'Administrador Norte (DEMO)',
                            'email' => 'norte@pepsi-demo.test',
                            'password' => $branchAdminPassword,
                            'role' => 'branch_admin',
                            'branch_id' => $branch->id,
                        ]);
                    }
                }
            });

            $this->info($name.' creado.');
            $this->line('  '.$email.' | clave: '.$password);
            if ($branchAdminPassword) {
                $this->line('  norte@pepsi-demo.test | clave: '.$branchAdminPassword);
            }
        }

        foreach (Company::where('is_demo', true)->whereIn('slug', ['pepsi-demo', 'coca-cola-demo'])->get() as $company) {
            $this->seedCrm($company);
        }

        return self::SUCCESS;
    }

    private function seedCrm(Company $company): void
    {
        if ($company->slug === 'pepsi-demo' && ! $company->users()->where('email', 'demo.crm@pepsi-demo.test')->exists()) {
            $password = bin2hex(random_bytes(12));
            $company->users()->create([
                'name' => 'Administrador CRM (DEMO)',
                'email' => 'demo.crm@pepsi-demo.test',
                'password' => $password,
                'role' => 'company_admin',
                'branch_id' => $company->branches()->where('is_central', true)->firstOrFail()->id,
            ]);
            $this->line('  demo.crm@pepsi-demo.test | clave: '.$password);
        }

        $examples = $company->slug === 'pepsi-demo'
            ? [
                ['Central', 'María Torres', 'Distribuidora Atlas', 'maria@atlas.test', 'customer', 'Acuerdo de distribución', 2500000, 'won'],
                ['Norte', 'Carlos Méndez', 'Mercados Norte', 'carlos@mercados-norte.test', 'qualified', 'Expansión regional', 8500000, 'proposal'],
            ]
            : [
                ['Central', 'Lucía Herrera', 'Comercial Sol', 'lucia@comercial-sol.test', 'prospect', 'Primer pedido', 1750000, 'qualified'],
                ['Oeste', 'Javier Ruiz', 'Tiendas Pacífico', 'javier@pacifico.test', 'customer', 'Renovación anual', 4200000, 'new'],
            ];

        foreach ($examples as [$branchName, $name, $organization, $email, $status, $title, $amount, $stage]) {
            $branch = $company->branches()->where('name', $branchName)->first();
            if (! $branch) {
                continue;
            }
            $contact = $company->contacts()->firstOrCreate(
                ['email' => $email],
                ['branch_id' => $branch->id, 'name' => $name, 'organization' => $organization,
                    'status' => $status, 'source' => 'Datos de demostración', 'is_demo' => true]
            );
            $contact->opportunities()->firstOrCreate(
                ['title' => $title],
                ['company_id' => $company->id, 'branch_id' => $branch->id,
                    'amount_cents' => $amount, 'stage' => $stage, 'is_demo' => true]
            );
        }
        $this->line('CRM de '.$company->name.' listo con datos DEMO.');
    }
}
