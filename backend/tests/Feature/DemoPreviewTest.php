<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Company;
use App\Models\Credential;
use App\Models\CredentialTemplate;
use App\Models\MobileSession;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoPreviewTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(): User
    {
        return User::create([
            'name' => 'Responsable global',
            'email' => 'global@example.test',
            'password' => 'correct-horse-battery',
            'role' => 'super_admin',
        ]);
    }

    public function test_super_admin_can_manage_demo_credentials_and_return_without_accessing_real_company_secrets(): void
    {
        $real = app(CreateCompany::class)->handle('Empresa Real', 'admin@real.test', 'correct-horse-battery');
        $realAdmin = $real->users()->firstOrFail();
        $realTemplate = CredentialTemplate::create(['company_id' => $real->id, 'name' => 'Privada real']);
        $realField = $realTemplate->fields()->create(['name' => 'Clave', 'type' => 'password', 'required' => true, 'position' => 0]);
        $realCredential = Credential::create(['company_id' => $real->id, 'owner_id' => $realAdmin->id, 'template_id' => $realTemplate->id, 'title' => 'Secreto real', 'visibility' => 'private']);
        $realCredential->values()->create(['field_id' => $realField->id, 'secret_ciphertext' => encrypt('hidden-real-secret')]);

        $demo = app(CreateCompany::class)->handle('Pepsi Demo', 'admin@demo.test', 'correct-horse-battery', true);
        $demoAdmin = $demo->users()->firstOrFail();
        $employee = $demo->users()->create([
            'name' => 'Empleado DEMO', 'email' => 'empleado@demo.test', 'password' => 'correct-horse-battery',
            'role' => 'employee', 'branch_id' => $demoAdmin->branch_id, 'approval_status' => 'approved',
        ]);
        $super = $this->superAdmin();

        $this->actingAs($super)->get(route('dashboard'))->assertOk()
            ->assertSee('Probar empresas DEMO')->assertSee('Probar Pepsi Demo')
            ->assertDontSee('Probar Empresa Real');
        $this->get(route('credentials.index'))->assertOk()->assertDontSee('Secreto real');
        $this->get(route('credential-templates.index'))->assertForbidden();
        $this->post(route('companies.preview', $real))->assertForbidden();

        $this->post(route('companies.preview', $demo))->assertRedirect(route('credentials.index'));
        $this->assertAuthenticatedAs($demoAdmin);
        $this->assertDatabaseHas('mobile_sessions', [
            'user_id' => $super->id, 'status' => 'closed', 'end_reason' => 'demo_preview_started',
        ]);
        $this->assertDatabaseHas('mobile_sessions', [
            'user_id' => $demoAdmin->id, 'status' => 'active', 'auth_method' => 'demo_preview',
        ]);
        $this->get(route('credentials.index'))->assertOk()->assertSee('Modo DEMO')
            ->assertSee('Plantillas')->assertSee('Volver a plataforma')->assertDontSee('Secreto real');
        $this->get(route('credential-templates.index'))->assertOk();
        $this->get(route('credential-templates.create'))->assertOk();

        $this->post(route('credential-templates.store'), [
            'name' => 'Acceso DEMO',
            'fields' => [
                ['name' => 'Usuario', 'type' => 'text', 'required' => 1],
                ['name' => 'Clave', 'type' => 'password', 'required' => 1],
                ['name' => 'Enlace', 'type' => 'url'],
            ],
        ])->assertRedirect(route('credential-templates.index'));
        $template = CredentialTemplate::where('company_id', $demo->id)->firstOrFail()->load('fields');
        $fields = $template->fields->keyBy('type');
        $this->post(route('credential-templates.shares.store', $template), [
            'user_id' => $employee->id, 'max_accesses' => 2,
        ])->assertRedirect();
        $this->post(route('credentials.store'), [
            'title' => 'Portal DEMO', 'template_id' => $template->id, 'visibility' => 'shared',
            'values' => [
                $fields['text']->id => 'demo@example.test',
                $fields['password']->id => 'demo-only-secret',
                $fields['url']->id => 'https://example.test',
            ],
        ])->assertRedirect();
        $credential = Credential::where('company_id', $demo->id)->firstOrFail();
        $this->post(route('credentials.grants.store', $credential), [
            'user_id' => $employee->id, 'level' => 'view', 'can_reveal' => 1,
        ])->assertRedirect();
        $this->delete(route('credentials.grants.destroy', [$credential, $employee]))->assertRedirect();
        $this->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'max_accesses' => 2,
            'duration_value' => 1, 'duration_unit' => 'days', 'can_reveal' => 1,
        ])->assertRedirect();
        $this->assertDatabaseHas('credential_shares', [
            'credential_id' => $credential->id, 'user_id' => $employee->id, 'max_accesses' => 2,
        ]);
        $this->get(route('credentials.show', $credential))->assertOk();
        $this->get(route('credentials.show', $realCredential))->assertForbidden();
        $this->get(route('credential-templates.show', $realTemplate))->assertForbidden();
        $this->postJson(route('credentials.reveal', [$realCredential, $realField]))->assertForbidden();
        $this->get(route('password.edit'))->assertForbidden();
        $this->put(route('password.update'), [
            'current_password' => 'correct-horse-battery', 'password' => 'changed-password-123',
            'password_confirmation' => 'changed-password-123',
        ])->assertForbidden();
        $this->assertDatabaseHas('activity_logs', [
            'company_id' => $demo->id, 'actor_id' => $super->id,
            'event' => 'credential.created',
        ]);
        $this->assertFalse(ActivityLog::where('properties', 'like', '%demo-only-secret%')->exists());

        $this->post(route('demo.exit'))->assertRedirect(route('companies.show', $demo));
        $this->assertAuthenticatedAs($super);
        $this->assertDatabaseHas('mobile_sessions', [
            'user_id' => $demoAdmin->id, 'status' => 'closed', 'end_reason' => 'demo_preview_ended',
        ]);
        $this->assertSame(1, MobileSession::where('user_id', $super->id)->where('status', 'active')->count());
        $this->get(route('companies.show', $demo))->assertOk()->assertSee('Probar Pepsi Demo');
        $this->get(route('dashboard'))->assertOk()->assertSee('Administración global');
        $this->get(route('credentials.index'))->assertOk()->assertDontSee('Portal DEMO');
        $this->get(route('credentials.show', $credential))->assertForbidden();
    }

    public function test_preview_requires_active_demo_company_admin_and_super_admin(): void
    {
        $demo = app(CreateCompany::class)->handle('Cola Demo', 'admin@cola.test', 'correct-horse-battery', true);
        $demoAdmin = $demo->users()->firstOrFail();
        $super = $this->superAdmin();

        $this->actingAs($demoAdmin)->post(route('companies.preview', $demo))->assertForbidden();
        $this->actingAs($super)->post(route('demo.exit'))->assertForbidden();
        $demoAdmin->update(['is_active' => false]);
        $this->post(route('companies.preview', $demo))->assertSessionHasErrors('demo_preview');
        $this->assertAuthenticatedAs($super);
        $demo->update(['is_active' => false]);
        $this->post(route('companies.preview', $demo))->assertForbidden();
    }

    public function test_preview_ends_if_original_super_admin_is_suspended(): void
    {
        $demo = app(CreateCompany::class)->handle('Demo Azul', 'admin@azul.test', 'correct-horse-battery', true);
        $super = $this->superAdmin();
        $this->actingAs($super)->post(route('companies.preview', $demo))->assertRedirect();
        $super->update(['is_active' => false]);

        $this->get(route('credentials.index'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
