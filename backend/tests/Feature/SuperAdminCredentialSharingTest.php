<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Credential;
use App\Models\CredentialShare;
use App\Models\CredentialTemplate;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class SuperAdminCredentialSharingTest extends TestCase
{
    use RefreshDatabase;

    private function superAdmin(string $email = 'platform@example.test'): User
    {
        return User::create([
            'name' => 'Superadministrador', 'email' => $email,
            'password' => 'correct-horse-battery', 'role' => 'super_admin',
        ]);
    }

    private function companyCredential(string $name, bool $demo = false, string $visibility = 'shared'): array
    {
        $company = app(CreateCompany::class)->handle($name, 'admin@'.strtolower($name).'.test', 'correct-horse-battery', $demo);
        $admin = $company->users()->firstOrFail();
        $template = CredentialTemplate::create(['company_id' => $company->id, 'name' => 'Acceso '. $name]);
        $field = $template->fields()->create(['name' => 'Clave', 'type' => 'password', 'required' => true, 'position' => 0]);
        $credential = Credential::create([
            'company_id' => $company->id, 'template_id' => $template->id,
            'owner_id' => $admin->id, 'title' => 'Portal '. $name, 'visibility' => $visibility,
        ]);
        $credential->values()->create(['field_id' => $field->id, 'secret_ciphertext' => Crypt::encryptString('fake-'.$name.'-secret')]);

        return [$company, $admin, $credential, $field];
    }

    public function test_real_and_demo_companies_can_share_read_only_with_one_super_admin(): void
    {
        [$real, $realAdmin, $realCredential, $realField] = $this->companyCredential('Real');
        [$demo, $demoAdmin, $demoCredential] = $this->companyCredential('Demo', true);
        [$other, $otherAdmin, $otherCredential] = $this->companyCredential('Other');
        [$private, $privateAdmin, $privateCredential] = $this->companyCredential('Private', false, 'private');
        $recipient = $this->superAdmin();
        $otherSuper = $this->superAdmin('other-platform@example.test');

        $this->actingAs($realAdmin)->get(route('credentials.show', $realCredential))->assertOk()
            ->assertSee('Superadministradores de plataforma')->assertSee($recipient->email);
        $this->actingAs($privateAdmin)->get(route('credentials.show', $privateCredential))->assertOk()
            ->assertDontSee('Superadministradores de plataforma');
        $this->post(route('credentials.shares.store', $privateCredential), [
            'user_id' => $recipient->id, 'max_accesses' => 2,
        ])->assertForbidden();
        $this->actingAs($realAdmin)->post(route('credentials.shares.store', $realCredential), [
            'user_id' => $otherAdmin->id, 'max_accesses' => 2,
        ])->assertNotFound();
        $this->post(route('credentials.shares.store', $realCredential), [
            'user_id' => $recipient->id, 'max_accesses' => 2,
            'duration_value' => 1, 'duration_unit' => 'days',
        ])->assertRedirect();
        $this->actingAs($demoAdmin)->post(route('credentials.shares.store', $demoCredential), [
            'user_id' => $recipient->id, 'max_accesses' => 3,
        ])->assertRedirect();
        $this->assertDatabaseCount('credential_shares', 2);

        $this->actingAs($recipient)->get(route('credentials.index'))->assertOk()
            ->assertSee('Compartidas conmigo')->assertSee('Portal Real')->assertSee('Portal Demo')
            ->assertDontSee('Portal Other')->assertDontSee('Portal Private')
            ->assertDontSee('Nueva credencial')->assertDontSee('Agregar a favoritas');
        $this->get(route('credentials.index', ['section' => 'mine', 'company' => $real->id, 'q' => 'Real']))
            ->assertOk()->assertSee('Portal Real')->assertDontSee('Portal Demo');
        $this->get(route('credentials.show', $realCredential))->assertOk()
            ->assertSee($real->name)->assertDontSee('fake-Real-secret')
            ->assertDontSee('Editar credencial')->assertDontSee('Compartir en solo lectura');
        $this->postJson(route('credentials.reveal', [$realCredential, $realField]))->assertForbidden();
        $this->get(route('credentials.show', $privateCredential))->assertForbidden();
        $this->get(route('credentials.show', $otherCredential))->assertForbidden();
        $this->actingAs($otherSuper)->get(route('credentials.index'))->assertOk()->assertDontSee('Portal Real');
        $this->get(route('credentials.show', $realCredential))->assertForbidden();
        $this->actingAs($recipient)->get(route('credentials.show', $realCredential))->assertOk();
        $this->assertSame(2, CredentialShare::where('credential_id', $realCredential->id)->firstOrFail()->used_accesses);
        $this->get(route('credentials.show', $realCredential))->assertForbidden();
        $this->get(route('credentials.index'))->assertOk()->assertDontSee('Portal Real')->assertSee('Portal Demo');
        $this->put(route('credentials.update', $demoCredential), ['title' => 'Cambiado'])->assertForbidden();
        $this->delete(route('credentials.destroy', $demoCredential))->assertForbidden();
        $this->post(route('credentials.shares.store', $demoCredential), [
            'user_id' => $otherSuper->id, 'max_accesses' => 2,
        ])->assertForbidden();
        $this->assertFalse(ActivityLog::where('properties', 'like', '%fake-Real-secret%')->exists());
    }

    public function test_reveal_needs_explicit_consent_and_access_ends_on_expiration_or_revocation(): void
    {
        [$company, $admin, $credential, $field] = $this->companyCredential('Secure');
        $recipient = $this->superAdmin();
        $this->actingAs($admin)->post(route('credentials.shares.store', $credential), [
            'user_id' => $recipient->id, 'max_accesses' => 1, 'can_reveal' => 1,
            'duration_value' => 1, 'duration_unit' => 'hours',
        ])->assertRedirect();
        $share = CredentialShare::firstOrFail();
        $this->actingAs($recipient)->postJson(route('credentials.reveal', [$credential, $field]))->assertForbidden();
        $this->get(route('credentials.show', $credential))->assertOk()->assertSee('data-reveal-url=', false);
        $this->postJson(route('credentials.reveal', [$credential, $field]))
            ->assertOk()->assertJsonPath('value', 'fake-Secure-secret');
        $share->update(['revoked_at' => now()]);
        $this->postJson(route('credentials.reveal', [$credential, $field]))->assertForbidden();
        $this->get(route('credentials.show', $credential))->assertForbidden();
        $this->get(route('credentials.index'))->assertOk()->assertDontSee('Portal Secure');

        $share->update(['revoked_at' => null, 'expires_at' => now()->subSecond()]);
        $this->get(route('credentials.show', $credential))->assertForbidden();
        $this->get(route('credentials.index'))->assertOk()->assertDontSee('Portal Secure');
        $this->actingAs($admin)->put(route('credentials.update', $credential), [
            'title' => $credential->title, 'visibility' => 'private',
        ])->assertSessionHasErrors('visibility');
    }

    public function test_private_records_stay_hidden_even_with_a_stale_share_and_suspended_companies_disappear(): void
    {
        [$company, $admin, $shared] = $this->companyCredential('Restricted');
        [$otherCompany, $otherAdmin, $private, $privateField] = $this->companyCredential('Protected', false, 'private');
        $recipient = $this->superAdmin();
        $private->shares()->create([
            'company_id' => $otherCompany->id, 'user_id' => $recipient->id,
            'shared_by_id' => $otherAdmin->id, 'max_accesses' => 5,
        ]);
        $shared->shares()->create([
            'company_id' => $company->id, 'user_id' => $recipient->id,
            'shared_by_id' => $admin->id, 'max_accesses' => 5,
        ]);

        $this->actingAs($recipient)->get(route('credentials.index'))->assertOk()
            ->assertSee('Portal Restricted')->assertDontSee('Portal Protected');
        $this->get(route('credentials.show', $private))->assertForbidden();
        $this->postJson(route('credentials.reveal', [$private, $privateField]))->assertForbidden();
        $company->update(['is_active' => false]);
        $this->get(route('credentials.index'))->assertOk()->assertDontSee('Portal Restricted');
        $this->get(route('credentials.show', $shared))->assertForbidden();
    }
}
