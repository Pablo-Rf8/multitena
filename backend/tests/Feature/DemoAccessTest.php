<?php

namespace Tests\Feature;

use App\Models\Credential;
use App\Models\CredentialTemplate;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_companies_remain_available_without_super_admin_preview(): void
    {
        $demo = app(CreateCompany::class)->handle('Pepsi Demo', 'admin@demo.test', 'correct-horse-battery', true);
        $demoAdmin = $demo->users()->firstOrFail();
        $real = app(CreateCompany::class)->handle('Empresa Real', 'admin@real.test', 'correct-horse-battery');
        $template = CredentialTemplate::create(['company_id' => $real->id, 'name' => 'Privada real']);
        Credential::create([
            'company_id' => $real->id, 'owner_id' => $real->users()->firstOrFail()->id,
            'template_id' => $template->id, 'title' => 'Secreto real', 'visibility' => 'private',
        ]);
        $superAdmin = User::create([
            'name' => 'Responsable global', 'email' => 'global@example.test',
            'password' => 'correct-horse-battery', 'role' => 'super_admin',
        ]);

        $this->actingAs($superAdmin)->get(route('dashboard'))->assertOk()
            ->assertSee('Pepsi Demo')->assertDontSee('Probar empresas DEMO')
            ->assertDontSee('Secreto real');
        $this->get(route('companies.show', $demo))->assertOk()
            ->assertSee('Pepsi Demo')->assertDontSee('Acceso de prueba a Credenciales');
        $this->post('/companies/'.$demo->id.'/preview')->assertNotFound();
        $this->get(route('credentials.index'))->assertOk()->assertDontSee('Secreto real');

        $this->actingAs($demoAdmin)->get(route('credentials.index'))->assertOk()
            ->assertDontSee('Modo DEMO')->assertDontSee('Secreto real');
        $this->get(route('credential-templates.create'))->assertOk();
    }

    public function test_existing_preview_session_can_still_return_to_platform(): void
    {
        $demo = app(CreateCompany::class)->handle('Pepsi Demo', 'admin@demo.test', 'correct-horse-battery', true);
        $demoAdmin = $demo->users()->firstOrFail();
        $superAdmin = User::create([
            'name' => 'Responsable global', 'email' => 'global@example.test',
            'password' => 'correct-horse-battery', 'role' => 'super_admin',
        ]);

        $this->actingAs($demoAdmin)->withSession(['demo_preview' => [
            'super_admin_id' => $superAdmin->id,
            'company_id' => $demo->id,
            'demo_user_id' => $demoAdmin->id,
        ]]);
        $this->post(route('demo.exit'))->assertRedirect(route('companies.show', $demo));
        $this->assertAuthenticatedAs($superAdmin);
        $this->get(route('dashboard'))->assertOk()->assertDontSee('Modo DEMO');
    }
}
