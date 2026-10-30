<?php

namespace Tests\Feature;

use App\Models\CodeAuthAttempt;
use App\Models\CodeLink;
use App\Models\MobileSession;
use App\Models\User;
use App\Services\CodeLinks;
use App\Services\CreateCompany;
use App\Services\TemporaryCodes;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CodeAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_individual_totp_codes_change_every_thirty_seconds_and_secrets_are_encrypted(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $first = $this->employee($company, 'eva@pepsi.test');
        $second = $this->employee($company, 'luis@pepsi.test');
        $this->actingAs($super);
        app(CodeLinks::class)->issue($first, $super);
        app(CodeLinks::class)->issue($second, $super);

        $codes = app(TemporaryCodes::class);
        $credential = $first->codeCredential;
        $other = $second->codeCredential;
        $this->assertNotSame($credential->secret, $other->secret);
        $this->assertNotSame($credential->secret, DB::table('code_credentials')->where('user_id', $first->id)->value('secret'));
        $this->assertMatchesRegularExpression('/^\d{6}$/', $codes->code($credential, 12345));
        $this->assertNotSame($codes->code($credential, 12345), $codes->code($credential, 12346));
        $this->assertNotSame($codes->code($credential, 12345), $codes->code($other, 12345));
    }

    public function test_link_expires_at_exactly_120_hours_and_requires_identity_before_showing_code(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-30 12:00:00'));
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $employee = $this->employee($company, 'eva@pepsi.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        [$link, $url] = app(CodeLinks::class)->issue($employee, $super);
        $token = explode('#', $url)[1];

        $this->assertSame(120 * 3600, $link->expires_at->getTimestamp() - $link->issued_at->getTimestamp());
        $this->assertSame(hash('sha256', $token), $link->token_hash);
        $this->assertNotSame($token, DB::table('code_links')->where('id', $link->id)->value('handoff_token'));
        $this->get('/codes/generator')->assertRedirect('/codes/access');
        $this->post('/codes/verify', ['link_token' => $token, 'email' => $employee->email, 'password' => 'wrong-password'])->assertSessionHasErrors('identity');
        $this->post('/codes/verify', ['link_token' => $token, 'email' => $employee->email, 'password' => 'employee-password-123'])->assertRedirect('/codes/generator');
        $current = $this->get('/codes/current')->assertOk()->assertJsonPath('status', 'active');
        $this->assertStringContainsString('no-store', $current->headers->get('Cache-Control'));

        $this->travelTo($link->expires_at);
        $this->assertFalse($link->fresh()->isUsable());
        $this->post('/codes/verify', ['link_token' => $token, 'email' => $employee->email, 'password' => 'employee-password-123'])->assertRedirect('/codes/generator');
        $this->get('/codes/current')->assertForbidden()->assertJsonPath('status', 'expired');
        $this->get('/codes/generator')->assertOk()->assertSee('Enlace vencido')->assertDontSee(app(TemporaryCodes::class)->code($employee->codeCredential));
        $this->travelBack();
    }

    public function test_valid_code_logs_in_once_and_rejects_wrong_expired_and_reused_codes(): void
    {
        $this->travelTo(CarbonImmutable::parse('2026-10-30 12:00:00'));
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $employee = $this->employee($company, 'eva@pepsi.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        app(CodeLinks::class)->issue($employee, $super);
        $this->post('/logout');
        $this->post('/login/company', ['company_code' => $company->code]);

        $codes = app(TemporaryCodes::class);
        $current = $codes->code($employee->codeCredential);
        $wrong = $current === '000000' ? '000001' : '000000';
        $this->post('/login/code', ['email' => $employee->email, 'code' => $wrong])->assertSessionHasErrors('code');
        $this->post('/login/code', ['email' => $employee->email, 'code' => $current])->assertRedirect('/dashboard');
        $this->assertAuthenticatedAs($employee);
        $this->assertDatabaseHas('mobile_sessions', ['user_id' => $employee->id, 'auth_method' => 'totp', 'status' => 'active']);
        $this->post('/logout');
        $this->post('/login/code', ['email' => $employee->email, 'code' => $current])->assertSessionHasErrors('code');
        $this->assertDatabaseHas('code_auth_attempts', ['user_id' => $employee->id, 'result' => 'failed', 'reason' => 'reused']);

        $this->travelTo(now()->addSeconds(30));
        $this->post('/login/code', ['email' => $employee->email, 'code' => $current])->assertSessionHasErrors('code');
        $this->assertDatabaseHas('code_auth_attempts', ['user_id' => $employee->id, 'result' => 'failed', 'reason' => 'incorrect']);
        $this->assertSame(1, CodeAuthAttempt::where('user_id', $employee->id)->where('result', 'success')->count());
        $this->assertNotNull(CodeAuthAttempt::where('result', 'success')->firstOrFail()->session_hash);
        $this->assertFalse(DB::table('code_auth_attempts')->where('reason', $current)->exists());
        $this->travelBack();
    }

    public function test_suspending_link_blocks_generator_and_revokes_web_and_mobile_sessions(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $employee = $this->employee($company, 'eva@pepsi.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        [$link, $url] = app(CodeLinks::class)->issue($employee, $super);
        $this->post('/codes/verify', ['link_token' => explode('#', $url)[1], 'email' => $employee->email, 'password' => 'employee-password-123'])->assertRedirect();

        $this->actingAs($employee);
        $this->get('/dashboard')->assertOk();
        $session = MobileSession::where('user_id', $employee->id)->where('channel', 'web')->firstOrFail();
        $this->actingAs($super)->post('/codes/links/'.$link->id.'/suspend')->assertRedirect();
        $this->assertDatabaseHas('mobile_sessions', ['id' => $session->id, 'status' => 'revoked', 'end_reason' => 'code_link_suspended']);
        $this->get('/codes/current')->assertForbidden()->assertJsonPath('status', 'suspended');
        $this->actingAs($employee)->withSession(['access_session_id' => $session->id])->get('/dashboard')->assertRedirect('/login');
        $this->assertDatabaseHas('activity_logs', ['event' => 'code_link.suspended', 'subject_id' => $link->id]);
    }

    public function test_only_superadmin_manages_links_and_company_views_are_tenant_scoped(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $pepsiEmployee = $this->employee($pepsi, 'eva@pepsi.test');
        $cocaEmployee = $this->employee($coca, 'luis@coca.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        [$pepsiLink] = app(CodeLinks::class)->issue($pepsiEmployee, $super);
        app(CodeLinks::class)->issue($cocaEmployee, $super);

        $pepsiAdmin = $pepsi->users()->where('role', 'company_admin')->firstOrFail();
        $cocaAdmin = $coca->users()->where('role', 'company_admin')->firstOrFail();
        $this->actingAs($cocaAdmin)->get('/codes/company')->assertOk()->assertSee('Luis')->assertDontSee('Eva');
        $this->post('/codes/links/'.$pepsiLink->id.'/claim')->assertNotFound();
        $this->post('/codes/users/'.$cocaEmployee->id.'/issue')->assertForbidden();
        $this->get('/codes/admin')->assertForbidden();
        $this->get('/codes/history')->assertForbidden();

        $this->actingAs($pepsiAdmin)->post('/codes/links/'.$pepsiLink->id.'/claim')->assertOk();
        $this->post('/codes/links/'.$pepsiLink->id.'/claim')->assertStatus(409);
        $this->actingAs($super)->get('/codes/admin')->assertOk()->assertSee('Gestión de códigos y accesos');
        $this->get('/codes/history')->assertOk();
        $this->post('/codes/links/'.$pepsiLink->id.'/revoke')->assertRedirect();
        $this->assertDatabaseHas('code_links', ['id' => $pepsiLink->id, 'status' => 'revoked']);
    }

    public function test_renewal_invalidates_old_link_and_company_admin_can_claim_new_link_only_once(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $employee = $this->employee($company, 'eva@pepsi.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        [$old, $oldUrl] = app(CodeLinks::class)->issue($employee, $super);
        [$new, $newUrl] = app(CodeLinks::class)->issue($employee, $super);
        $this->assertSame('revoked', $old->fresh()->status);
        $this->assertNull($old->fresh()->handoff_token);
        $this->assertFalse($old->fresh()->isUsable());
        $this->assertNotSame($oldUrl, $newUrl);

        $this->actingAs($company->users()->firstOrFail());
        $this->post('/codes/links/'.$old->id.'/claim')->assertStatus(409);
        $this->post('/codes/links/'.$new->id.'/claim')->assertOk()->assertSee($newUrl);
        $this->post('/codes/links/'.$new->id.'/claim')->assertStatus(409);
        $this->assertNull($new->fresh()->handoff_token);
        $this->post('/codes/verify', ['link_token' => explode('#', $oldUrl)[1], 'email' => $employee->email, 'password' => 'employee-password-123'])->assertRedirect('/codes/generator');
        $this->get('/codes/current')->assertForbidden()->assertJsonPath('status', 'revoked');
    }

    public function test_bulk_suspension_revokes_mobile_token_and_records_attempts_for_global_history(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $employee = $this->employee($pepsi, 'eva@pepsi.test');
        $other = $this->employee($coca, 'luis@coca.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        [$first] = app(CodeLinks::class)->issue($employee, $super);
        [$second] = app(CodeLinks::class)->issue($pepsi->users()->firstOrFail(), $super);
        [$untouched] = app(CodeLinks::class)->issue($other, $super);
        $this->post('/logout');
        $this->post('/login/company', ['company_code' => $pepsi->code]);
        $code = app(TemporaryCodes::class)->code($employee->codeCredential);
        $this->post('/login/code', ['email' => $employee->email, 'code' => $code])->assertRedirect('/dashboard');
        $this->post('/logout');
        $token = $this->postJson('/api/login', [
            'email' => $employee->email,
            'password' => 'employee-password-123',
            'company_code' => $pepsi->code,
            'device_id' => 'phone-12345678',
            'device_name' => 'Teléfono de prueba',
        ])->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/session')->assertOk();

        $this->actingAs($super)->post('/codes/companies/'.$pepsi->id.'/suspend')->assertRedirect();
        $this->assertSame('suspended', $first->fresh()->status);
        $this->assertSame('suspended', $second->fresh()->status);
        $this->assertSame('active', $untouched->fresh()->status);
        $this->assertDatabaseHas('mobile_sessions', ['user_id' => $employee->id, 'channel' => 'mobile', 'status' => 'revoked', 'end_reason' => 'code_link_suspended']);
        $this->withToken($token)->getJson('/api/session')->assertUnauthorized();
        $this->get('/codes/history?company_id='.$pepsi->id.'&result=success')->assertOk()->assertSee('<td>Eva</td>', false)->assertDontSee('<td>Luis</td>', false);
    }

    public function test_rate_limit_records_blocked_attempts_and_superadmin_cannot_use_code_login(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $employee = $this->employee($company, 'eva@pepsi.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        app(CodeLinks::class)->issue($employee, $super);
        $this->post('/logout');
        $this->post('/login/company', ['company_code' => $company->code]);
        $correct = app(TemporaryCodes::class)->code($employee->codeCredential);
        $wrong = $correct === '000000' ? '000001' : '000000';
        for ($i = 0; $i < 5; $i++) $this->post('/login/code', ['email' => $employee->email, 'code' => $wrong])->assertSessionHasErrors('code');
        $this->post('/login/code', ['email' => $employee->email, 'code' => $correct])->assertSessionHasErrors('code');
        $this->assertDatabaseHas('code_auth_attempts', ['user_id' => $employee->id, 'reason' => 'rate_limited']);
        $this->assertGuest();
        $this->post('/login/code', ['email' => $super->email, 'code' => $correct])->assertSessionHasErrors('code');
        $this->assertDatabaseMissing('code_auth_attempts', ['user_id' => $super->id, 'result' => 'success']);
    }

    public function test_company_and_branch_administrators_use_individual_codes_only_in_their_company(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $companyAdmin = $pepsi->users()->where('role', 'company_admin')->firstOrFail();
        $branchAdmin = $pepsi->users()->create([
            'name' => 'Admin sucursal',
            'email' => 'sucursal@pepsi.test',
            'password' => 'strong-password-123',
            'role' => 'branch_admin',
            'branch_id' => $pepsi->branches()->firstOrFail()->id,
        ]);
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        [, $companyUrl] = app(CodeLinks::class)->issue($companyAdmin, $super);
        app(CodeLinks::class)->issue($branchAdmin, $super);
        $this->post('/logout');

        $this->post('/login/company', ['company_code' => $coca->code]);
        $this->post('/login/code', [
            'email' => $companyAdmin->email,
            'code' => app(TemporaryCodes::class)->code($companyAdmin->codeCredential),
        ])->assertSessionHasErrors('code');
        $this->assertGuest();

        $this->post('/login/company', ['company_code' => $pepsi->code]);
        $this->post('/codes/verify', [
            'link_token' => explode('#', $companyUrl)[1],
            'email' => $branchAdmin->email,
            'password' => 'strong-password-123',
        ])->assertSessionHasErrors('identity');
        $this->get('/codes/current')->assertUnauthorized();

        foreach ([$companyAdmin, $branchAdmin] as $admin) {
            $this->post('/login/code', [
                'email' => $admin->email,
                'code' => app(TemporaryCodes::class)->code($admin->codeCredential),
            ])->assertRedirect('/dashboard');
            $this->assertAuthenticatedAs($admin);
            $this->post('/logout')->assertRedirect('/login');
        }
    }

    public function test_mobile_code_login_uses_the_same_one_time_code_and_revokes_its_token(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $employee = $this->employee($pepsi, 'eva@pepsi.test');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super);
        [$link] = app(CodeLinks::class)->issue($employee, $super);
        $this->post('/logout');

        $payload = [
            'email' => $employee->email,
            'company_code' => $pepsi->code,
            'device_id' => 'phone-12345678',
            'device_name' => 'Teléfono de prueba',
            'code' => app(TemporaryCodes::class)->code($employee->codeCredential),
        ];
        $this->postJson('/api/login/code', array_merge($payload, ['company_code' => $coca->code]))->assertUnauthorized();
        $this->postJson('/api/login/code', array_merge($payload, ['code' => 'abcdef']))->assertUnprocessable();
        $token = $this->postJson('/api/login/code', $payload)->assertOk()->json('token');
        $this->assertDatabaseHas('mobile_sessions', ['user_id' => $employee->id, 'auth_method' => 'totp', 'status' => 'active']);
        $this->assertDatabaseHas('code_auth_attempts', ['user_id' => $employee->id, 'result' => 'success']);
        $this->postJson('/api/login/code', $payload)->assertUnauthorized();
        $this->assertDatabaseHas('code_auth_attempts', ['user_id' => $employee->id, 'reason' => 'reused']);
        $this->withToken($token)->getJson('/api/session')->assertOk();

        $this->actingAs($super)->post('/codes/links/'.$link->id.'/suspend')->assertRedirect();
        $this->withToken($token)->getJson('/api/session')->assertUnauthorized();
    }

    private function employee($company, string $email): User
    {
        return $company->users()->create([
            'name' => str_starts_with($email, 'eva') ? 'Eva' : 'Luis',
            'email' => $email,
            'password' => 'employee-password-123',
            'role' => 'employee',
            'branch_id' => $company->branches()->firstOrFail()->id,
            'approval_status' => 'approved',
        ]);
    }
}
