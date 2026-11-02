<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyDomainAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_selects_only_its_company_and_existing_code_still_works(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.com', 'password-for-pepsi');
        $coca = app(CreateCompany::class)->handle('Coca-Cola', 'admin@coca.com', 'password-for-coca');
        CompanySetting::updateOrCreate(['company_id' => $pepsi->id], ['email_domains' => ['pepsi.com']]);
        CompanySetting::updateOrCreate(['company_id' => $coca->id], ['email_domains' => ['coca.com']]);

        $this->post('/login/company', ['company_code' => 'admin@pepsi.com'])->assertRedirect('/login');
        $this->get('/login')->assertSee('Pepsi');
        $this->post('/login', ['email' => 'admin@coca.com', 'password' => 'password-for-coca'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'admin@pepsi.com', 'password' => 'password-for-pepsi'])->assertRedirect('/dashboard');

        auth()->logout();
        $this->post('/login/company', ['company_code' => $coca->code])->assertRedirect('/login');
        $this->get('/login')->assertSee('Coca-Cola');
    }

    public function test_domains_cannot_be_assigned_to_two_companies_and_employees_are_grouped_by_company(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.com', 'password-for-pepsi');
        $coca = app(CreateCompany::class)->handle('Coca-Cola', 'admin@coca.com', 'password-for-coca');
        CompanySetting::updateOrCreate(['company_id' => $pepsi->id], ['email_domains' => ['pepsi.com']]);
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        User::factory()->create(['name' => 'Empleado Pepsi', 'role' => 'employee', 'company_id' => $pepsi->id, 'branch_id' => $pepsi->branches()->firstOrFail()->id]);
        User::factory()->create(['name' => 'Empleado Coca', 'role' => 'employee', 'company_id' => $coca->id, 'branch_id' => $coca->branches()->firstOrFail()->id]);

        $this->actingAs($super)->put('/companies/'.$coca->id, ['name' => $coca->name, 'currency' => 'GTQ', 'email_domains' => 'pepsi.com'])->assertSessionHasErrors('email_domains');
        $this->actingAs($super)->get('/companies/'.$pepsi->id.'/employees')->assertOk()->assertSee('Empleado Pepsi')->assertDontSee('Empleado Coca');
        $this->actingAs($pepsi->users()->where('role', 'company_admin')->firstOrFail())->get('/companies/'.$coca->id.'/employees')->assertForbidden();
    }

    public function test_individual_link_and_temporary_code_routes_are_unavailable(): void
    {
        $this->get('/codes/access')->assertNotFound();
        $this->get('/login/code')->assertNotFound();
        $this->postJson('/api/login/code', [])->assertNotFound();
    }

    public function test_mobile_login_can_resolve_the_company_from_the_email_domain(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.com', 'password-for-pepsi');
        $coca = app(CreateCompany::class)->handle('Coca-Cola', 'admin@coca.com', 'password-for-coca');
        CompanySetting::updateOrCreate(['company_id' => $pepsi->id], ['email_domains' => ['pepsi.com']]);
        CompanySetting::updateOrCreate(['company_id' => $coca->id], ['email_domains' => ['coca.com']]);
        $login = ['email' => 'admin@pepsi.com', 'password' => 'password-for-pepsi', 'device_id' => 'mobile-pepsi-123', 'device_name' => 'Teléfono Pepsi'];

        $this->postJson('/api/login', array_merge($login, ['company_code' => $coca->code]))->assertUnauthorized();
        $this->postJson('/api/login', $login)->assertOk()->assertJsonPath('user.company_id', $pepsi->id);
    }
}
