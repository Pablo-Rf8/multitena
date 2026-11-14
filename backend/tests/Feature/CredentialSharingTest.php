<?php

namespace Tests\Feature;

use App\Models\Credential;
use App\Models\CredentialShare;
use App\Models\CredentialTemplate;
use App\Models\User;
use App\Services\Audit;
use App\Services\CreateCompany;
use App\Services\OpenCredential;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class CredentialSharingTest extends TestCase
{
    use RefreshDatabase;

    private function company(string $name): array
    {
        $company = app(CreateCompany::class)->handle($name, 'admin@'.strtolower($name).'.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();
        $employee = $company->users()->create([
            'name' => 'Empleado '.$name, 'email' => 'employee@'.strtolower($name).'.test',
            'password' => 'correct-horse-battery', 'role' => 'employee',
            'branch_id' => $admin->branch_id, 'approval_status' => 'approved',
        ]);

        return [$company, $admin, $employee];
    }

    private function credential(User $admin, string $title = 'Portal DEMO'): array
    {
        $template = CredentialTemplate::create(['company_id' => $admin->company_id, 'name' => 'Acceso web']);
        $password = $template->fields()->create(['name' => 'Clave', 'type' => 'password', 'position' => 0, 'required' => true]);
        $credential = Credential::create([
            'company_id' => $admin->company_id, 'template_id' => $template->id,
            'owner_id' => $admin->id, 'title' => $title, 'visibility' => 'shared',
        ]);
        $credential->values()->create(['field_id' => $password->id, 'secret_ciphertext' => Crypt::encryptString('demo-secret')]);

        return [$credential, $password];
    }

    public function test_rendered_sections_and_sharing_forms_expose_only_allowed_actions(): void
    {
        [$company, $admin, $employee] = $this->company('Pepsi');
        [$credential] = $this->credential($admin);
        $this->actingAs($admin);

        foreach (['mine' => 'Mis credenciales', 'shared' => 'Compartidas conmigo', 'favorites' => 'Favoritas'] as $section => $label) {
            $this->get(route('credentials.index', ['section' => $section]))
                ->assertOk()->assertSee($label)->assertSee('name="section" value="'.$section.'"', false)
                ->assertSee('name="q"', false)->assertSee('name="template"', false)
                ->assertSee('name="visibility"', false)->assertSee('Filtrar');
        }

        $this->get(route('credentials.show', $credential))->assertOk()
            ->assertSee('Compartir en solo lectura')->assertSee('Historial de aperturas')
            ->assertSee('Agregar a favoritas');
        $this->get(route('credentials.sharing', $credential))->assertOk()
            ->assertSee('action="'.route('credentials.shares.store', $credential).'"', false)
            ->assertSee('name="max_accesses"', false)
            ->assertSee('value="hours"', false)->assertSee('value="days"', false)
            ->assertSee('value="weeks"', false)->assertSee('value="months"', false)
            ->assertSee('name="can_reveal"', false);
        $this->get(route('credentials.permissions', $credential))->assertOk()->assertSee('Guardar permiso');
        $this->get(route('credentials.history', $credential))->assertOk()->assertSee('Historial de aperturas');
        $this->get(route('credential-templates.show', $credential->template))->assertOk()
            ->assertSee('Compartir plantilla vacía');
        $this->get(route('credential-templates.sharing', $credential->template))->assertOk()
            ->assertSee('value="months"', false);

        $this->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'max_accesses' => 3,
        ])->assertRedirect();
        $this->actingAs($employee)->get(route('credentials.show', $credential))->assertOk()
            ->assertSee('Agregar a favoritas')->assertDontSee('Compartir en solo lectura')
            ->assertDontSee('Editar credencial')->assertDontSee('data-reveal-url=', false)
            ->assertDontSee('data-copy-secret', false);
        $this->get(route('credentials.sharing', $credential))->assertForbidden();
        $this->get(route('credentials.permissions', $credential))->assertForbidden();
        $this->get(route('credentials.history', $credential))->assertForbidden();
        $this->get(route('credential-templates.show', $credential->template))->assertForbidden();
        $this->get(route('credential-templates.sharing', $credential->template))->assertForbidden();
    }

    public function test_filters_and_duration_units_are_applied_from_rendered_forms(): void
    {
        [$company, $admin, $employee] = $this->company('Pepsi');
        [$credential] = $this->credential($admin);
        [$other] = $this->credential($admin, 'Otra credencial');
        $this->actingAs($admin)->post(route('credentials.favorite.store', $credential))->assertRedirect();
        $this->post(route('credentials.favorite.store', $other))->assertRedirect();
        $this->get(route('credentials.index', [
            'section' => 'favorites', 'q' => 'Portal', 'template' => $credential->template_id, 'visibility' => 'shared',
        ]))->assertOk()->assertSee('Portal DEMO')->assertDontSee('Otra credencial');
        $this->get(route('credentials.index', [
            'section' => 'mine', 'visibility' => 'private',
        ]))->assertOk()->assertDontSee('Portal DEMO');

        $this->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'duration_value' => 1,
        ])->assertSessionHasErrors('duration_unit');
        $this->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'max_accesses' => 0,
            'duration_value' => 2, 'duration_unit' => 'weeks', 'can_reveal' => 1,
        ])->assertSessionHasErrors('max_accesses');
        $this->get(route('credentials.sharing', $credential))->assertOk()
            ->assertSee('name="duration_value" min="1" max="365" value="2"', false)
            ->assertSee('value="weeks" selected', false)
            ->assertSee('name="can_reveal" value="1" checked', false);
        $this->post(route('credential-templates.shares.store', $credential->template), [
            'user_id' => $employee->id, 'max_accesses' => 0,
            'duration_value' => 3, 'duration_unit' => 'months',
        ])->assertSessionHasErrors('max_accesses');
        $this->get(route('credential-templates.sharing', $credential->template))->assertOk()
            ->assertSee('name="duration_value" min="1" max="365" value="3"', false)
            ->assertSee('value="months" selected', false);
        foreach (['hours', 'days', 'weeks', 'months'] as $unit) {
            $this->post(route('credentials.shares.store', $credential), [
                'user_id' => $employee->id, 'duration_value' => '1', 'duration_unit' => $unit,
            ])->assertRedirect();
            $share = CredentialShare::latest('id')->firstOrFail();
            $expected = match ($unit) {
                'hours' => now()->addHour(), 'days' => now()->addDay(),
                'weeks' => now()->addWeek(), 'months' => now()->addMonthNoOverflow(),
            };
            $this->assertSame($expected->format('Y-m-d H:i'), $share->expires_at->format('Y-m-d H:i'));
            $this->delete(route('credentials.shares.destroy', [$credential, $share]))->assertRedirect();
        }
        $this->actingAs($employee)->get(route('credentials.index', ['section' => 'shared']))
            ->assertOk()->assertDontSee('Portal DEMO');
    }

    public function test_sections_and_favorites_are_personal_and_filterable(): void
    {
        [$company, $admin, $employee] = $this->company('Pepsi');
        [$portal] = $this->credential($admin);
        [$other] = $this->credential($admin, 'Otro acceso');
        $this->actingAs($admin)->post(route('credentials.shares.store', $portal), [
            'user_id' => $employee->id, 'max_accesses' => 5,
        ])->assertRedirect();
        $this->post(route('credentials.shares.store', $portal), [
            'user_id' => $employee->id, 'max_accesses' => 5,
        ])->assertSessionHasErrors('user_id');
        $this->post(route('credentials.grants.store', $portal), [
            'user_id' => $employee->id, 'level' => 'edit',
        ])->assertSessionHasErrors('user_id');
        $this->assertDatabaseCount('credential_shares', 1);

        $this->get(route('credentials.index', ['section' => 'mine', 'q' => 'Portal']))
            ->assertOk()->assertSee('Portal DEMO')->assertDontSee('Otro acceso');
        $this->post(route('credentials.favorite.store', $portal))->assertRedirect();
        $this->get(route('credentials.index', ['section' => 'favorites']))
            ->assertOk()->assertSee('Portal DEMO')->assertDontSee('Otro acceso');

        $this->actingAs($employee)->get(route('credentials.index', ['section' => 'mine']))->assertOk()->assertDontSee('Portal DEMO');
        $this->get(route('credentials.index', ['section' => 'shared', 'q' => 'Portal']))->assertOk()->assertSee('Portal DEMO');
        $this->get(route('credentials.index', ['section' => 'shared', 'q' => 'Otro']))->assertOk()->assertDontSee('Portal DEMO');
        $this->get(route('credentials.index', ['section' => 'favorites']))->assertOk()->assertDontSee('Portal DEMO');
        $this->post(route('credentials.favorite.store', $portal))->assertRedirect();
        $this->get(route('credentials.index', ['section' => 'favorites']))->assertOk()->assertSee('Portal DEMO');
        $this->assertDatabaseCount('credential_favorites', 2);
        $this->delete(route('credentials.favorite.destroy', $portal))->assertRedirect();
        $this->assertDatabaseCount('credential_favorites', 1);
    }

    public function test_limited_share_is_read_only_and_counts_only_successful_openings(): void
    {
        [$company, $admin, $employee] = $this->company('Pepsi');
        [$credential, $field] = $this->credential($admin);
        $this->actingAs($admin)->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'max_accesses' => 2, 'can_reveal' => 1,
        ])->assertRedirect();
        $share = CredentialShare::firstOrFail();

        $this->actingAs($employee)->get(route('credentials.index', ['section' => 'shared']))->assertOk()->assertSee('Portal DEMO');
        $this->assertSame(0, $share->fresh()->used_accesses);
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertForbidden();
        $this->get(route('credentials.show', $credential))->assertOk()->assertDontSee('demo-secret')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame(1, $share->fresh()->used_accesses);
        $this->post(route('credentials.favorite.store', $credential))
            ->assertRedirect(route('credentials.index', ['section' => 'favorites']));
        $this->assertSame(1, $share->fresh()->used_accesses);
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertOk()->assertJsonPath('value', 'demo-secret');
        $this->get(route('credentials.edit', $credential))->assertForbidden();
        $this->put(route('credentials.update', $credential), ['title' => 'Cambio', 'visibility' => 'shared'])->assertForbidden();
        $this->delete(route('credentials.destroy', $credential))->assertForbidden();
        $this->post(route('credentials.shares.store', $credential), ['user_id' => $admin->id, 'max_accesses' => 100])->assertForbidden();
        $this->get(route('credentials.show', $credential))->assertOk();
        $this->assertSame(2, $share->fresh()->used_accesses);
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertOk();
        $this->get(route('credentials.show', $credential))->assertForbidden();
        $this->get(route('credentials.index', ['section' => 'shared']))->assertOk()->assertDontSee('Portal DEMO');
        $this->get(route('credentials.index', ['section' => 'favorites']))->assertOk()->assertDontSee('Portal DEMO');
        $this->assertSame(2, $share->fresh()->used_accesses);
        $this->assertDatabaseCount('credential_accesses', 2);

        $this->actingAs($admin)->get(route('credentials.show', $credential))->assertOk()->assertSee('Historial de aperturas');
        $this->assertSame(2, $share->fresh()->used_accesses);
    }

    public function test_expiration_revocation_and_company_boundaries_are_enforced(): void
    {
        [$pepsi, $admin, $employee] = $this->company('Pepsi');
        [$coca, $otherAdmin, $otherEmployee] = $this->company('Coca');
        [$credential, $field] = $this->credential($admin);
        $private = Credential::create([
            'company_id' => $pepsi->id, 'template_id' => $credential->template_id,
            'owner_id' => $admin->id, 'title' => 'Solo administración', 'visibility' => 'private',
        ]);
        $this->actingAs($admin)->post(route('credentials.shares.store', $private), [
            'user_id' => $employee->id, 'max_accesses' => 2,
        ])->assertForbidden();
        $this->actingAs($admin)->post(route('credentials.shares.store', $credential), [
            'user_id' => $otherEmployee->id, 'max_accesses' => 2,
        ])->assertNotFound();
        $this->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id,
        ])->assertSessionHasErrors('max_accesses');
        $this->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'duration_value' => 1, 'duration_unit' => 'hours', 'can_reveal' => 1,
        ])->assertRedirect();
        $share = CredentialShare::firstOrFail();
        $this->actingAs($otherAdmin)->get(route('credentials.show', $credential))->assertForbidden();
        $this->actingAs($employee)->get(route('credentials.show', $credential))->assertOk();
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertOk();
        $this->travel(61)->minutes();
        $this->get(route('credentials.show', $credential))->assertForbidden();
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertForbidden();
        $this->actingAs($admin)->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'max_accesses' => 3, 'can_reveal' => 1,
        ])->assertRedirect();
        $newShare = CredentialShare::latest('id')->firstOrFail();
        $this->actingAs($employee)->get(route('credentials.show', $credential))->assertOk();
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertOk();
        $this->actingAs($admin);
        $this->delete(route('credentials.shares.destroy', [$credential, $newShare]))->assertRedirect();
        $this->actingAs($employee)->get(route('credentials.show', $credential))->assertForbidden();
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertForbidden();
        $this->assertNotNull($newShare->fresh()->revoked_at);
        $this->assertSame(1, $share->fresh()->used_accesses);
    }

    public function test_conditional_increment_rejects_a_stale_second_open(): void
    {
        [$company, $admin, $employee] = $this->company('Pepsi');
        [$credential] = $this->credential($admin);
        $this->actingAs($admin)->post(route('credentials.shares.store', $credential), [
            'user_id' => $employee->id, 'max_accesses' => 1,
        ])->assertRedirect();
        $share = CredentialShare::firstOrFail();
        $this->actingAs($employee);
        $this->assertTrue(Gate::forUser($employee)->allows('view', $credential));
        $this->assertTrue(Gate::forUser($employee)->allows('view', $credential));
        app(OpenCredential::class)->handle($credential, $employee, app(Audit::class));
        try {
            app(OpenCredential::class)->handle($credential, $employee, app(Audit::class));
            $this->fail('A stale authorization must not consume another opening.');
        } catch (HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
        $this->assertSame(1, $share->fresh()->used_accesses);
        $this->assertDatabaseCount('credential_accesses', 1);
    }

    public function test_empty_template_can_be_shared_without_exposing_saved_credentials(): void
    {
        [$pepsi, $admin, $employee] = $this->company('Pepsi');
        [$coca, $otherAdmin, $otherEmployee] = $this->company('Coca');
        [$credential] = $this->credential($admin, 'Portal privado DEMO');
        $template = $credential->template;
        $this->actingAs($admin)->post(route('credential-templates.shares.store', $template), [
            'user_id' => $otherEmployee->id, 'max_accesses' => 2,
        ])->assertNotFound();
        $this->post(route('credential-templates.shares.store', $template), [
            'user_id' => $employee->id, 'max_accesses' => 1,
        ])->assertRedirect();
        $share = $template->shares()->firstOrFail();

        $this->actingAs($employee)->get(route('credential-templates.index'))->assertOk()->assertSee('Acceso web')->assertDontSee('Portal privado DEMO');
        $this->assertSame(0, $share->fresh()->used_accesses);
        $this->get(route('credential-templates.show', $template))->assertOk()
            ->assertSee('Clave')->assertDontSee('demo-secret')->assertDontSee('Portal privado DEMO')
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->get(route('credentials.show', $credential))->assertForbidden();
        $this->get(route('credential-templates.show', $template))->assertForbidden();
        $this->assertSame(1, $share->fresh()->used_accesses);
        $this->actingAs($otherEmployee)->get(route('credential-templates.show', $template))->assertForbidden();
    }
}
