<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Credential;
use App\Models\CredentialTemplate;
use App\Models\CredentialValue;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class CredentialManagementTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $name): array
    {
        $company = app(CreateCompany::class)->handle($name, 'admin@'.strtolower($name).'.test', 'correct-horse-battery');

        return [$company, $company->users()->firstOrFail()];
    }

    private function template(User $admin): CredentialTemplate
    {
        $this->actingAs($admin)->post(route('credential-templates.store'), [
            'name' => 'Acceso web',
            'fields' => [
                ['name' => 'Usuario', 'type' => 'text', 'required' => 1],
                ['name' => 'Clave', 'type' => 'password', 'required' => 1],
                ['name' => 'Sitio', 'type' => 'url'],
            ],
        ])->assertRedirect(route('credential-templates.index'));

        return CredentialTemplate::where('company_id', $admin->company_id)->firstOrFail()->load('fields');
    }

    public function test_template_fields_can_be_created_reordered_edited_and_deleted(): void
    {
        [$company, $admin] = $this->company('Pepsi');
        $this->actingAs($admin)->get(route('credential-templates.create'))->assertOk();
        $template = $this->template($admin);
        $this->get(route('credential-templates.index'))->assertOk();
        $this->get(route('credential-templates.edit', $template))->assertOk();
        $this->assertSame(['text', 'password', 'url'], $template->fields->pluck('type')->all());
        $this->assertTrue($template->fields[0]->required);
        $this->actingAs($admin)->put(route('credential-templates.update', $template), [
            'name' => 'Acceso editado',
            'fields' => [
                ['id' => $template->fields[2]->id, 'name' => 'Enlace', 'type' => 'url'],
                ['id' => $template->fields[0]->id, 'name' => 'Login', 'type' => 'text', 'required' => 1],
            ],
        ])->assertRedirect();
        $this->assertSame(['Enlace', 'Login'], $template->fresh()->fields->pluck('name')->all());
        $this->actingAs($admin)->delete(route('credential-templates.destroy', $template))->assertRedirect();
        $this->assertDatabaseCount('credential_templates', 0);
    }

    public function test_template_form_keeps_sparse_field_indexes_after_validation_error(): void
    {
        [$company, $admin] = $this->company('Pepsi');
        $this->actingAs($admin)->post(route('credential-templates.store'), [
            'name' => '',
            'fields' => [2 => ['name' => 'Usuario', 'type' => 'text']],
        ])->assertSessionHasErrors('name');
        $this->get(route('credential-templates.create'))->assertOk()
            ->assertSee('data-next-index="3"', false)
            ->assertSee('name="fields[2][name]"', false);
    }

    public function test_password_is_encrypted_and_reveal_has_a_separate_permission(): void
    {
        [$company, $admin] = $this->company('Pepsi');
        $template = $this->template($admin);
        $this->get(route('credentials.create', ['template' => $template->id]))->assertOk();
        $fields = $template->fields->keyBy('type');
        $secret = 'my-test-secret-123';
        $this->actingAs($admin)->post(route('credentials.store'), [
            'title' => '', 'template_id' => $template->id, 'visibility' => 'shared',
            'values' => [$fields['password']->id => $secret],
        ])->assertSessionHasErrors('title');
        $this->assertNull(session('_old_input.values'));
        $this->post(route('credentials.store'), [
            'title' => 'Invalid URL', 'template_id' => $template->id, 'visibility' => 'shared',
            'values' => [$fields['text']->id => 'user@example.test', $fields['password']->id => $secret, $fields['url']->id => 'javascript:alert(1)'],
        ])->assertSessionHasErrors('values.'.$fields['url']->id);
        $this->assertNull(session('_old_input.values'));
        $this->actingAs($admin)->post(route('credentials.store'), [
            'title' => 'Portal', 'template_id' => $template->id, 'visibility' => 'shared',
            'values' => [$fields['text']->id => 'user@example.test', $fields['password']->id => $secret, $fields['url']->id => 'https://example.test/login'],
        ])->assertRedirect();
        $credential = Credential::firstOrFail();
        $this->get(route('credentials.edit', $credential))->assertOk()->assertDontSee($secret);
        $stored = CredentialValue::where('field_id', $fields['password']->id)->firstOrFail();
        $this->assertNull($stored->value);
        $this->assertNotSame($secret, $stored->secret_ciphertext);
        $this->assertSame($secret, Crypt::decryptString($stored->secret_ciphertext));

        $employee = $company->users()->create(['name' => 'Empleado', 'email' => 'empleado@pepsi.test', 'password' => 'correct-horse-battery', 'role' => 'employee', 'branch_id' => $admin->branch_id, 'approval_status' => 'approved']);
        $this->actingAs($admin)->post(route('credentials.grants.store', $credential), ['user_id' => $employee->id, 'level' => 'view'])->assertRedirect();
        $this->actingAs($employee)->get(route('credentials.show', $credential))->assertOk()->assertDontSee($secret)->assertSee('user@example.test');
        $this->postJson(route('credentials.reveal', [$credential, $fields['password']]))->assertForbidden();
        $this->put(route('credentials.update', $credential), ['title' => 'Editado', 'visibility' => 'shared'])->assertForbidden();

        $this->actingAs($admin)->post(route('credentials.grants.store', $credential), ['user_id' => $employee->id, 'level' => 'view', 'can_reveal' => 1])->assertRedirect();
        $this->actingAs($employee)->postJson(route('credentials.reveal', [$credential, $fields['password']]))->assertOk()->assertJsonPath('value', $secret)->assertHeader('Cache-Control', 'no-store, private');
        $this->put(route('credentials.update', $credential), ['title' => 'Editado', 'visibility' => 'shared'])->assertForbidden();

        $this->actingAs($admin)->post(route('credentials.grants.store', $credential), ['user_id' => $employee->id, 'level' => 'edit'])->assertRedirect();
        $this->actingAs($employee)->postJson(route('credentials.reveal', [$credential, $fields['password']]))->assertForbidden();
        $this->put(route('credentials.update', $credential), ['title' => 'Editado', 'visibility' => 'shared', 'values' => [$fields['text']->id => 'new@example.test']])->assertRedirect();
        $this->assertSame('Editado', $credential->fresh()->title);
        $this->assertSame($secret, Crypt::decryptString($stored->fresh()->secret_ciphertext));
        $this->assertFalse(ActivityLog::where('properties', 'like', '%'.$secret.'%')->exists());
        $this->actingAs($admin)->delete(route('credentials.grants.destroy', [$credential, $employee]))->assertRedirect();
        $this->actingAs($employee)->get(route('credentials.show', $credential))->assertForbidden();
        $this->postJson(route('credentials.reveal', [$credential, $fields['password']]))->assertForbidden();
    }

    public function test_private_and_cross_company_access_are_denied(): void
    {
        [$pepsi, $admin] = $this->company('Pepsi');
        [$coca, $cocaAdmin] = $this->company('Coca');
        $template = $this->template($admin);
        $credential = Credential::create(['company_id' => $pepsi->id, 'template_id' => $template->id, 'owner_id' => $admin->id, 'title' => 'Portal secreto Pepsi', 'visibility' => 'private']);
        $employee = $pepsi->users()->create(['name' => 'Empleado', 'email' => 'empleado@pepsi.test', 'password' => 'correct-horse-battery', 'role' => 'employee', 'branch_id' => $admin->branch_id, 'approval_status' => 'approved']);
        $branchAdmin = $pepsi->users()->create(['name' => 'Admin sucursal', 'email' => 'sucursal@pepsi.test', 'password' => 'correct-horse-battery', 'role' => 'branch_admin', 'branch_id' => $admin->branch_id]);

        $this->actingAs($employee)->get(route('credentials.show', $credential))->assertForbidden();
        $this->actingAs($admin)->post(route('credentials.grants.store', $credential), ['user_id' => $employee->id, 'level' => 'view'])->assertForbidden();
        $this->post(route('credentials.grants.store', $credential), ['user_id' => $branchAdmin->id, 'level' => 'view'])->assertRedirect();
        $this->actingAs($branchAdmin)->get(route('credentials.show', $credential))->assertOk();
        $this->postJson(route('credentials.reveal', [$credential, $template->fields->firstWhere('type', 'password')]))->assertForbidden();
        $this->actingAs($admin)->post(route('credentials.grants.store', $credential), ['user_id' => $cocaAdmin->id, 'level' => 'view'])->assertNotFound();
        $this->actingAs($cocaAdmin)->get(route('credentials.index'))->assertOk()->assertDontSee('Portal secreto Pepsi');
        $this->get(route('credentials.show', $credential))->assertForbidden();
        $this->get(route('credentials.edit', $credential))->assertForbidden();
        $this->put(route('credentials.update', $credential), ['title' => 'Intrusión', 'visibility' => 'private'])->assertForbidden();
        $this->postJson(route('credentials.reveal', [$credential, $template->fields->firstWhere('type', 'password')]))->assertForbidden();
        $this->get(route('credential-templates.edit', $template))->assertForbidden();
        $this->post(route('credentials.store'), ['title' => 'Intento', 'template_id' => $template->id, 'visibility' => 'shared'])->assertNotFound();
        $super = User::factory()->create(['role' => 'super_admin']);
        $this->actingAs($super)->get(route('dashboard'))->assertOk()->assertSee('Gestión de Credenciales')->assertDontSee('Portal secreto Pepsi');
        $this->get(route('credentials.index'))->assertForbidden();
        $this->get(route('credential-templates.index'))->assertForbidden();
        $this->get(route('credentials.show', $credential))->assertForbidden();
    }

    public function test_template_with_credentials_cannot_be_deleted_or_lose_stored_fields(): void
    {
        [$company, $admin] = $this->company('Pepsi');
        $template = $this->template($admin);
        $credential = Credential::create(['company_id' => $company->id, 'template_id' => $template->id, 'owner_id' => $admin->id, 'title' => 'Portal', 'visibility' => 'private']);
        $field = $template->fields[0];
        CredentialValue::create(['credential_id' => $credential->id, 'field_id' => $field->id, 'value' => 'user@example.test']);
        $this->actingAs($admin)->delete(route('credential-templates.destroy', $template))->assertSessionHasErrors('template');
        $this->put(route('credential-templates.update', $template), ['name' => 'Changed', 'fields' => [['name' => 'Solo nuevo', 'type' => 'text']]])->assertSessionHasErrors('fields');
        $this->assertDatabaseHas('credential_values', ['field_id' => $field->id, 'value' => 'user@example.test']);
    }

    public function test_empty_optional_fields_can_be_removed_from_a_used_template(): void
    {
        [$company, $admin] = $this->company('Pepsi');
        $template = $this->template($admin);
        $fields = $template->fields->keyBy('type');
        $this->actingAs($admin)->post(route('credentials.store'), [
            'title' => 'Portal', 'template_id' => $template->id, 'visibility' => 'private',
            'values' => [$fields['text']->id => 'usuario', $fields['password']->id => 'DemoSecret!123'],
        ])->assertRedirect();
        $this->assertDatabaseMissing('credential_values', ['field_id' => $fields['url']->id]);
        $this->put(route('credential-templates.update', $template), [
            'name' => 'Sin URL',
            'fields' => [
                ['id' => $fields['text']->id, 'name' => 'Usuario', 'type' => 'text', 'required' => 1],
                ['id' => $fields['password']->id, 'name' => 'Clave', 'type' => 'password', 'required' => 1],
            ],
        ])->assertRedirect();
        $this->assertSame(['text', 'password'], $template->fresh()->fields->pluck('type')->all());
        $this->actingAs($admin)->get(route('credentials.show', Credential::firstOrFail()))->assertOk()->assertDontSee('Sitio');
    }

    public function test_only_owner_can_delete_credential_and_then_unused_template(): void
    {
        [$company, $admin] = $this->company('Pepsi');
        $template = $this->template($admin);
        $fields = $template->fields->keyBy('type');
        $this->actingAs($admin)->post(route('credentials.store'), [
            'title' => 'Portal ventas', 'template_id' => $template->id, 'visibility' => 'shared',
            'values' => [$fields['text']->id => 'usuario', $fields['password']->id => 'DemoSecret!123'],
        ])->assertRedirect();
        $credential = Credential::firstOrFail();
        $employee = $company->users()->create(['name' => 'Empleado', 'email' => 'empleado@pepsi.test', 'password' => 'correct-horse-battery', 'role' => 'employee', 'branch_id' => $admin->branch_id, 'approval_status' => 'approved']);
        $this->post(route('credentials.grants.store', $credential), ['user_id' => $employee->id, 'level' => 'edit'])->assertRedirect();
        $this->actingAs($employee)->delete(route('credentials.destroy', $credential))->assertForbidden();
        $this->get(route('credentials.index', ['q' => 'Portal']))->assertOk()->assertSee('Portal ventas');
        $this->get(route('credentials.index', ['q' => 'Otro']))->assertOk()->assertDontSee('Portal ventas');
        $this->actingAs($admin)->delete(route('credentials.destroy', $credential))->assertRedirect(route('credentials.index'));
        $this->assertDatabaseCount('credentials', 0);
        $this->assertDatabaseCount('credential_values', 0);
        $this->assertDatabaseCount('credential_grants', 0);
        $this->delete(route('credential-templates.destroy', $template))->assertRedirect();
        $this->assertDatabaseCount('credential_templates', 0);
    }
}
