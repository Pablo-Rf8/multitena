<?php
namespace Tests\Feature;

use App\Models\{Category, CompanySetting, User};
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class WorkforceTest extends TestCase
{
    use RefreshDatabase;

    public function test_csv_import_reports_bad_rows_and_keeps_valid_employees_pending(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $csv = "name,email,password\nEva,eva@pepsi.test,secure-password-123\nBad,bad@pepsi.test,short\nEva Duplicate,eva@pepsi.test,secure-password-123\n";
        $this->actingAs($super)->post('/employees/import', [
            'company_id' => $company->id,
            'file' => UploadedFile::fake()->createWithContent('employees.csv', $csv),
        ])->assertRedirect()->assertSessionHas('import_errors');
        $this->assertDatabaseHas('users', ['email' => 'eva@pepsi.test', 'company_id' => $company->id, 'approval_status' => 'pending']);
        $this->assertDatabaseMissing('users', ['email' => 'bad@pepsi.test']);
    }

    public function test_employee_approval_and_tenant_scope(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super)->post('/employees', [
            'company_id' => $pepsi->id, 'name' => 'Eva', 'email' => 'eva@pepsi.test', 'phone' => '36117795',
            'password' => 'employee-password-123', 'password_confirmation' => 'employee-password-123',
        ])->assertRedirect();
        $employee = User::where('email', 'eva@pepsi.test')->firstOrFail();
        $this->assertSame('pending', $employee->approval_status);
        $this->assertSame('36117795', $employee->phone);
        $this->post('/logout');
        $this->post('/login/company', ['company_code' => $pepsi->code])->assertRedirect('/login');
        $this->post('/login', ['email' => $employee->email, 'password' => 'employee-password-123'])->assertSessionHasErrors();
        $this->actingAs($coca->users()->firstOrFail())->patch('/workforce/employees/'.$employee->id, ['decision' => 'approved'])->assertNotFound();
        $this->actingAs($pepsi->users()->firstOrFail())->get('/workforce')->assertOk()->assertSee('Eva');
        $category = Category::create(['company_id' => $pepsi->id, 'name' => 'Repartidor', 'permissions' => ['contacts.read']]);
        $this->actingAs($pepsi->users()->firstOrFail())->patch('/workforce/employees/'.$employee->id, ['decision' => 'approved', 'category_id' => $category->id])->assertRedirect();
        $this->assertSame('approved', $employee->fresh()->approval_status);
        $this->assertNotNull($employee->fresh()->reviewed_at);
        $this->assertSame($pepsi->users()->firstOrFail()->id, $employee->fresh()->reviewed_by);
    }

    public function test_mobile_token_is_scoped_and_device_revocation_invalidates_it(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $employee = $pepsi->users()->create([
            'name' => 'Eva', 'email' => 'eva@pepsi.test', 'password' => 'employee-password-123',
            'role' => 'employee', 'branch_id' => $pepsi->branches()->firstOrFail()->id,
            'approval_status' => 'approved',
        ]);
        $category = Category::create(['company_id' => $pepsi->id, 'name' => 'Repartidor', 'permissions' => ['contacts.read']]);
        $employee->update(['category_id' => $category->id]);
        CompanySetting::create(['company_id' => $pepsi->id]);
        $credentials = ['email' => $employee->email, 'password' => 'employee-password-123', 'company_code' => $pepsi->code, 'device_id' => 'device-unique-123', 'device_name' => 'Teléfono Eva'];
        $this->postJson('/api/login', array_merge($credentials, ['company_code' => $coca->code]))->assertUnauthorized();
        $token = $this->postJson('/api/login', $credentials)->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/session')->assertOk()->assertJsonPath('user.company_id', $pepsi->id);
        $this->withToken($token)->getJson('/api/contacts')->assertOk();
        $this->withToken($token)->getJson('/api/opportunities')->assertForbidden();
        $this->withToken($token)->postJson('/api/contacts', ['name' => 'Cliente', 'status' => 'prospect'])->assertForbidden();
        $this->withToken($token)->postJson('/api/opportunities', ['contact_id' => 999, 'title' => 'Venta', 'amount' => 100, 'stage' => 'new'])->assertForbidden();
        $device = $employee->devices()->firstOrFail();
        $this->actingAs($coca->users()->firstOrFail())->patch('/workforce/devices/'.$device->id, ['status' => 'revoked'])->assertNotFound();
        $this->actingAs($pepsi->users()->firstOrFail())->patch('/workforce/devices/'.$device->id, ['status' => 'revoked'])->assertRedirect();
        $this->withToken($token)->getJson('/api/session')->assertUnauthorized();
        $this->assertDatabaseHas('mobile_sessions', ['device_id' => $device->id, 'status' => 'revoked', 'end_reason' => 'device_revoked']);
    }

    public function test_mobile_logout_records_manual_close_and_revokes_token(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $token = $this->postJson('/api/login', [
            'email' => 'admin@pepsi.test', 'password' => 'strong-password-123',
            'company_code' => $company->code, 'device_id' => 'device-admin-123', 'device_name' => 'Admin phone',
        ])->assertOk()->json('token');
        $this->withToken($token)->getJson('/api/session')->assertOk();
        $this->withToken($token)->postJson('/api/logout')->assertOk();
        $this->withToken($token)->getJson('/api/session')->assertForbidden();
        $this->assertDatabaseHas('mobile_sessions', ['company_id' => $company->id, 'status' => 'closed', 'end_reason' => 'logout']);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_company_admin_can_manage_own_employees_and_categories_only(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $pepsiAdmin = $pepsi->users()->firstOrFail();
        $cocaAdmin = $coca->users()->firstOrFail();
        $this->actingAs($pepsiAdmin)->post('/workforce/categories', ['name' => 'Supervisor', 'permissions' => ['contacts.read']])->assertRedirect();
        $category = Category::where('company_id', $pepsi->id)->firstOrFail();
        $this->actingAs($cocaAdmin)->put('/workforce/categories/'.$category->id, ['name' => 'Intruso', 'is_active' => 0])->assertNotFound();
        $this->actingAs($pepsiAdmin)->put('/workforce/categories/'.$category->id, ['name' => 'Supervisor', 'is_active' => 1, 'permissions' => ['contacts.read', 'contacts.write']])->assertRedirect();
        $this->actingAs($pepsiAdmin)->post('/workforce/employees', ['name' => 'Eva', 'email' => 'eva@pepsi.test', 'phone' => '36117795', 'password' => 'employee-password-123', 'password_confirmation' => 'employee-password-123', 'branch_id' => $pepsiAdmin->branch_id])->assertRedirect();
        $employee = User::where('email', 'eva@pepsi.test')->firstOrFail();
        $this->assertSame('36117795', $employee->phone);
        $this->actingAs($cocaAdmin)->get('/workforce/employees/'.$employee->id.'/edit')->assertNotFound();
        $this->actingAs($pepsiAdmin)->put('/workforce/employees/'.$employee->id, ['name' => 'Eva', 'email' => 'eva@pepsi.test', 'phone' => '36117796', 'branch_id' => $pepsiAdmin->branch_id, 'is_active' => 1])->assertRedirect();
        $this->assertSame('36117796', $employee->fresh()->phone);
        $this->actingAs($pepsiAdmin)->patch('/workforce/employees/'.$employee->id, ['decision' => 'approved', 'category_id' => $category->id])->assertRedirect();
        $this->actingAs($pepsiAdmin)->patch('/workforce/employees/'.$employee->id.'/status', ['is_active' => 0])->assertRedirect();
        $this->assertFalse($employee->fresh()->is_active);
        $this->actingAs($pepsiAdmin)->delete('/workforce/categories/'.$category->id)->assertStatus(409);
    }

    public function test_web_session_is_recorded_and_admin_can_revoke_it(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $this->post('/login/company', ['company_code' => $company->code]);
        $this->post('/login', ['email' => 'admin@pepsi.test', 'password' => 'strong-password-123'])->assertRedirect();
        $session = \App\Models\MobileSession::where('channel', 'web')->where('company_id', $company->id)->firstOrFail();
        $this->assertNotNull($session->last_seen_at);
        $this->patch('/workforce/sessions/'.$session->id.'/end')->assertRedirect();
        $this->assertSame('admin_revoked', $session->fresh()->end_reason);
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_notification_center_is_private_to_recipient_and_company(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'strong-password-123');
        $pepsiAdmin = $pepsi->users()->firstOrFail();
        $cocaAdmin = $coca->users()->firstOrFail();
        \App\Services\AccessNotice::user($pepsiAdmin, 'employee_pending', 123, 'Empleado Pepsi');
        $notice = \Illuminate\Support\Facades\DB::table('access_notifications')->first();
        $this->actingAs($cocaAdmin)->get('/notifications')->assertOk()->assertDontSee('Empleado Pepsi');
        $this->actingAs($cocaAdmin)->patch('/notifications/'.$notice->id.'/read')->assertNotFound();
        $this->actingAs($pepsiAdmin)->get('/notifications')->assertOk()->assertSee('Empleado Pepsi');
        $this->actingAs($pepsiAdmin)->patch('/notifications/'.$notice->id.'/read')->assertRedirect();
    }

    public function test_mobile_device_can_register_encrypted_push_token_and_read_own_notices(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'strong-password-123');
        $token = $this->postJson('/api/login', ['email' => 'admin@pepsi.test', 'password' => 'strong-password-123', 'company_code' => $company->code, 'device_id' => 'device-admin-123', 'device_name' => 'Admin phone'])->assertOk()->json('token');
        $this->withToken($token)->putJson('/api/device/push-token', ['push_token' => 'example-fcm-token-for-tests'])->assertOk();
        $device = $company->users()->firstOrFail()->devices()->firstOrFail();
        $this->assertSame('example-fcm-token-for-tests', $device->fresh()->push_token);
        $this->assertNotSame('example-fcm-token-for-tests', \Illuminate\Support\Facades\DB::table('devices')->where('id', $device->id)->value('push_token'));
        \App\Services\AccessNotice::user($company->users()->firstOrFail(), 'device_approved', $device->id, 'Dispositivo autorizado');
        $this->withToken($token)->getJson('/api/notifications')->assertOk()->assertSee('Dispositivo autorizado');
    }
}
