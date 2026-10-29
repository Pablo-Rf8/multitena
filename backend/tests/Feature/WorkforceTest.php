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
            'company_id' => $pepsi->id, 'name' => 'Eva', 'email' => 'eva@pepsi.test',
            'password' => 'employee-password-123', 'password_confirmation' => 'employee-password-123',
        ])->assertRedirect();
        $employee = User::where('email', 'eva@pepsi.test')->firstOrFail();
        $this->assertSame('pending', $employee->approval_status);
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
}
