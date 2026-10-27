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
                $this->line($name.' ya existe; no se modificó.');

                continue;
            }

            $password = bin2hex(random_bytes(12));
            $branchAdminPassword = $name === 'Pepsi (DEMO)' ? bin2hex(random_bytes(12)) : null;

            DB::transaction(function () use ($creator, $name, $email, $password, $extraBranches, $branchAdminPassword) {
                $company = $creator->handle($name, $email, $password, true);
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

        return self::SUCCESS;
    }
}
