<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoDomainBackfillTest extends TestCase
{
    use RefreshDatabase;

    private function backfill(): void
    {
        $migration = require database_path('migrations/2026_11_02_000001_backfill_demo_email_domains.php');
        $migration->up();
    }

    public function test_it_fills_only_empty_demo_company_domains_and_is_repeatable(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi (DEMO)', 'admin@pepsi.test', 'correct-horse-battery', true);
        $coca = app(CreateCompany::class)->handle('Coca-Cola (DEMO)', 'admin@coca.test', 'correct-horse-battery', true);
        CompanySetting::create(['company_id' => $pepsi->id, 'email_domains' => []]);

        $this->backfill();
        $this->backfill();

        $this->assertSame(['pepsi-demo.test'], CompanySetting::where('company_id', $pepsi->id)->firstOrFail()->email_domains);
        $this->assertSame(['coca-cola-demo.test'], CompanySetting::where('company_id', $coca->id)->firstOrFail()->email_domains);
        $this->assertDatabaseCount('company_settings', 2);
    }

    public function test_it_preserves_custom_domains_and_never_claims_a_domain_used_by_another_company(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi (DEMO)', 'admin@pepsi.test', 'correct-horse-battery', true);
        $coca = app(CreateCompany::class)->handle('Coca-Cola (DEMO)', 'admin@coca.test', 'correct-horse-battery', true);
        $other = app(CreateCompany::class)->handle('Otra', 'admin@other.test', 'correct-horse-battery');
        CompanySetting::create(['company_id' => $pepsi->id, 'email_domains' => ['marca.example']]);
        CompanySetting::create(['company_id' => $other->id, 'email_domains' => ['COCA-COLA-DEMO.TEST']]);

        $this->backfill();

        $this->assertSame(['marca.example'], CompanySetting::where('company_id', $pepsi->id)->firstOrFail()->email_domains);
        $this->assertDatabaseMissing('company_settings', ['company_id' => $coca->id]);
        $this->assertSame(['COCA-COLA-DEMO.TEST'], CompanySetting::where('company_id', $other->id)->firstOrFail()->email_domains);
    }

    public function test_it_does_not_change_a_non_demo_company_with_a_matching_slug(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi (DEMO)', 'admin@pepsi.test', 'correct-horse-battery', false);

        $this->backfill();

        $this->assertDatabaseMissing('company_settings', ['company_id' => $company->id]);
    }
}
