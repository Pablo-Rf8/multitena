<?php
namespace Tests\Feature;

use App\Models\Category;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandingAndEmployeeWebTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_code_brands_first_login_and_rejects_other_tenant_credentials(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi (DEMO)', 'admin@pepsi.test', 'correct-password-123', true);
        $coca = app(CreateCompany::class)->handle('Coca-Cola (DEMO)', 'admin@coca.test', 'correct-password-123', true);
        $pepsi->update(['theme_primary' => '#0754a6']);
        $coca->update(['theme_primary' => '#bd1930']);

        $this->get('/login')->assertOk()->assertSee('Código de empresa')->assertDontSee('Pepsi (DEMO)');
        $this->post('/login', ['email' => 'admin@pepsi.test', 'password' => 'correct-password-123'])->assertSessionHasErrors('company_code');
        $this->post('/login/company', ['company_code' => 'NO-EXISTE'])->assertSessionHasErrors('company_code');
        $this->post('/login/company', ['company_code' => strtolower($pepsi->code)])->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Pepsi (DEMO)')->assertSee('#0754a6')->assertSee('/assets/demo/pepsi.svg');
        $this->post('/login', ['email' => 'admin@coca.test', 'password' => 'correct-password-123'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'admin@pepsi.test', 'password' => 'correct-password-123'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Pepsi (DEMO)')->assertDontSee('Coca-Cola (DEMO)');
        $this->post('/logout')->assertRedirect('/login');
        $this->get('/login/change')->assertRedirect('/login');
        $this->post('/login/company', ['company_code' => $coca->code])->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Coca-Cola (DEMO)')->assertSee('#bd1930')->assertSee('/assets/demo/coca-cola.svg');
    }

    public function test_platform_access_needs_no_company_code_and_cannot_log_in_as_tenant(): void
    {
        app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-password-123');
        $super = \App\Models\User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null, 'password' => 'super-password-123']);
        $this->get('/login/platform')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('Acceso exclusivo para superadministradores')->assertSee('theme-platform');
        $this->post('/login', ['email' => 'admin@pepsi.test', 'password' => 'correct-password-123'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => $super->email, 'password' => 'super-password-123'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('theme-platform');
    }

    public function test_company_themes_are_isolated_and_super_admin_stays_platform_branded(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi (DEMO)', 'admin@pepsi.test', 'correct-password-123', true);
        $coca = app(CreateCompany::class)->handle('Coca-Cola (DEMO)', 'admin@coca.test', 'correct-password-123', true);
        $pepsi->update(['theme_primary' => '#0754a6', 'theme_secondary' => '#102d5a', 'theme_accent' => '#d22739']);
        $coca->update(['theme_primary' => '#bd1930', 'theme_secondary' => '#711323', 'theme_accent' => '#f5d6d9']);
        $this->actingAs($pepsi->users()->firstOrFail())->get('/dashboard')->assertOk()->assertSee('theme-tenant')->assertSee('#0754a6')->assertDontSee('#bd1930');
        $this->actingAs($coca->users()->firstOrFail())->get('/dashboard')->assertOk()->assertSee('theme-tenant')->assertSee('#bd1930')->assertDontSee('#0754a6');
        $super = \App\Models\User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super)->get('/dashboard')->assertOk()->assertSee('theme-platform')->assertDontSee('theme-tenant');
    }

    public function test_employee_only_sees_permitted_sections_and_cannot_open_other_tenant(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-password-123');
        $coca = app(CreateCompany::class)->handle('Coca-Cola', 'admin@coca.test', 'correct-password-123');
        $category = Category::create(['company_id' => $pepsi->id, 'name' => 'Repartidor', 'permissions' => ['contacts.read']]);
        $employee = $pepsi->users()->create([
            'name' => 'Eva', 'email' => 'eva@pepsi.test', 'password' => 'employee-password-123',
            'role' => 'employee', 'branch_id' => $pepsi->branches()->firstOrFail()->id,
            'approval_status' => 'approved', 'category_id' => $category->id,
        ]);
        $foreign = $coca->contacts()->create(['branch_id' => $coca->branches()->firstOrFail()->id, 'name' => 'Privado', 'status' => 'prospect']);
        $this->actingAs($employee)->get('/dashboard')->assertOk()->assertSee('Mi espacio')->assertSee('Contactos')->assertDontSee('Oportunidades</a>');
        $this->get('/contacts')->assertOk()->assertDontSee('Agregar contacto')->assertDontSee('Privado');
        $this->get('/contacts/'.$foreign->id)->assertNotFound();
        $this->get('/contacts/create')->assertForbidden();
        $this->get('/opportunities')->assertForbidden();
        $this->get('/workforce')->assertForbidden();
    }

    public function test_company_admin_can_update_only_own_theme(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-password-123');
        $coca = app(CreateCompany::class)->handle('Coca-Cola', 'admin@coca.test', 'correct-password-123');
        $this->actingAs($pepsi->users()->firstOrFail())->put('/branding', [
            'theme_primary' => '#123456', 'theme_secondary' => '#234567', 'theme_accent' => '#345678',
        ])->assertRedirect();
        $this->assertSame('#123456', $pepsi->fresh()->theme_primary);
        $this->assertNotSame('#123456', $coca->fresh()->theme_primary);
    }

    public function test_demo_logo_can_be_selected_without_erasing_uploaded_logo_path(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi (DEMO)', 'admin@pepsi.test', 'correct-password-123', true);
        $pepsi->update(['logo_path' => 'company-logos/previous.png', 'logo_mode' => 'custom']);
        $this->actingAs($pepsi->users()->firstOrFail())->put('/branding', [
            'theme_primary' => '#0754a6', 'theme_secondary' => '#102d5a',
            'theme_accent' => '#d22739', 'logo_mode' => 'demo',
        ])->assertRedirect();
        $this->assertSame('company-logos/previous.png', $pepsi->fresh()->logo_path);
        $this->assertStringContainsString('/assets/demo/pepsi.svg', $pepsi->fresh()->logoUrl());
    }

    public function test_approved_employee_can_sign_in_to_web_but_pending_employee_cannot(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-password-123');
        $branch = $company->branches()->firstOrFail();
        foreach (['approved', 'pending'] as $state) {
            $company->users()->create([
                'name' => ucfirst($state), 'email' => $state.'@pepsi.test',
                'password' => 'employee-password-123', 'role' => 'employee',
                'branch_id' => $branch->id, 'approval_status' => $state,
            ]);
        }
        $this->post('/login/company', ['company_code' => $company->code])->assertRedirect('/login');
        $this->post('/login', ['email' => 'pending@pepsi.test', 'password' => 'employee-password-123'])->assertSessionHasErrors('email');
        $this->post('/login', ['email' => 'approved@pepsi.test', 'password' => 'employee-password-123'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Mi espacio');
    }
}
