<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class CrmAndLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_crm_records_follow_contact_branch_and_generate_activity(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        $this->actingAs($admin)->post('/contacts', [
            'name' => 'Ana Pérez', 'organization' => 'Cliente Uno', 'email' => 'ana@cliente.test',
            'status' => 'prospect', 'branch_id' => $branch->id,
        ])->assertRedirect();
        $contact = $company->contacts()->firstOrFail();
        $this->assertSame($branch->id, $contact->branch_id);

        $this->actingAs($admin)->post('/opportunities', [
            'contact_id' => $contact->id, 'title' => 'Pedido inicial', 'amount' => '129.50',
            'stage' => 'proposal',
        ])->assertRedirect();
        $opportunity = $company->opportunities()->firstOrFail();
        $this->assertSame(12950, $opportunity->amount_cents);
        $this->assertSame($branch->id, $opportunity->branch_id);

        $this->actingAs($admin)->post('/contacts/'.$contact->id.'/notes', ['body' => 'Llamar el viernes.'])->assertRedirect();
        $this->actingAs($admin)->get('/contacts/'.$contact->id)->assertOk()->assertSee('Llamar el viernes.')->assertSee('Pedido inicial');
        $this->actingAs($admin)->get('/opportunities')->assertOk()->assertSee('Pedido inicial');
        $this->assertDatabaseCount('activity_logs', 3);
        $this->assertSame('note.created', ActivityLog::orderByDesc('id')->firstOrFail()->event);
    }

    public function test_branch_admin_cannot_read_or_change_other_branch_or_company_crm(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'correct-horse-battery');
        $north = $pepsi->branches()->create(['name' => 'Norte']);
        $admin = $pepsi->users()->create([
            'name' => 'Norte Admin', 'email' => 'norte@pepsi.test', 'password' => 'correct-horse-battery',
            'role' => 'branch_admin', 'branch_id' => $north->id,
        ]);
        $own = $pepsi->contacts()->create(['branch_id' => $north->id, 'name' => 'Contacto Norte', 'status' => 'prospect']);
        $otherBranch = $pepsi->contacts()->create(['branch_id' => $pepsi->branches()->firstOrFail()->id, 'name' => 'Contacto Central', 'status' => 'prospect']);
        $otherCompany = $coca->contacts()->create(['branch_id' => $coca->branches()->firstOrFail()->id, 'name' => 'Contacto Coca', 'status' => 'prospect']);

        $this->actingAs($admin)->get('/contacts')->assertOk()->assertSee('Contacto Norte')->assertDontSee('Contacto Central')->assertDontSee('Contacto Coca');
        $this->actingAs($admin)->get('/contacts/'.$otherBranch->id)->assertNotFound();
        $this->actingAs($admin)->get('/contacts/'.$otherCompany->id.'/edit')->assertNotFound();
        $this->actingAs($admin)->post('/contacts/'.$otherBranch->id.'/notes', ['body' => 'Intrusión'])->assertNotFound();
        $this->actingAs($admin)->post('/opportunities', [
            'contact_id' => $otherCompany->id, 'title' => 'No autorizado', 'amount' => '10', 'stage' => 'new',
        ])->assertNotFound();
        $this->actingAs($admin)->post('/contacts', [
            'name' => 'No autorizado', 'status' => 'prospect', 'branch_id' => $otherCompany->branch_id,
        ])->assertSessionHasErrors('branch_id');
        $csv = $this->actingAs($admin)->get('/contacts/export')->streamedContent();
        $this->assertStringContainsString('Contacto Norte', $csv);
        $this->assertStringNotContainsString('Contacto Central', $csv);
        $this->assertStringNotContainsString('Contacto Coca', $csv);
        $this->assertDatabaseCount('opportunities', 0);
        $this->assertDatabaseCount('contact_notes', 0);
        $this->assertSame($own->id, $pepsi->contacts()->where('branch_id', $north->id)->firstOrFail()->id);
    }

    public function test_suspension_blocks_existing_session_and_login_without_deleting_data(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();
        $branch = $company->branches()->firstOrFail();

        $this->actingAs($super)->patch('/companies/'.$company->id.'/status', ['is_active' => '0'])->assertRedirect();
        $this->actingAs($admin)->get('/dashboard')->assertRedirect('/login');
        $this->post('/login', ['email' => $admin->email, 'password' => 'correct-horse-battery'])->assertSessionHasErrors('email');
        $this->assertDatabaseHas('companies', ['id' => $company->id, 'is_active' => false]);
        $this->assertDatabaseHas('branches', ['id' => $branch->id]);
        $this->assertDatabaseHas('users', ['id' => $admin->id]);

        $this->actingAs($super)->patch('/companies/'.$company->id.'/status', ['is_active' => '1'])->assertRedirect();
        $this->actingAs($admin->fresh())->get('/dashboard')->assertOk();
        $this->actingAs($admin->fresh())->patch('/branches/'.$branch->id.'/status', ['is_active' => '0'])->assertStatus(422);
    }

    public function test_company_admin_cannot_manage_another_company_branch_or_user(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'correct-horse-battery');
        $admin = $pepsi->users()->firstOrFail();
        $foreignBranch = $coca->branches()->firstOrFail();
        $foreignUser = $coca->users()->create([
            'name' => 'Sucursal Coca', 'email' => 'branch@coca.test', 'password' => 'correct-horse-battery',
            'role' => 'branch_admin', 'branch_id' => $foreignBranch->id,
        ]);

        $this->actingAs($admin)->get('/branches/'.$foreignBranch->id.'/edit')->assertNotFound();
        $this->actingAs($admin)->patch('/branches/'.$foreignBranch->id.'/status', ['is_active' => '0'])->assertNotFound();
        $this->actingAs($admin)->get('/branch-users/'.$foreignUser->id.'/edit')->assertNotFound();
        $this->actingAs($admin)->patch('/branch-users/'.$foreignUser->id.'/status', ['is_active' => '0'])->assertNotFound();
        $this->assertTrue($foreignBranch->fresh()->is_active);
        $this->assertTrue($foreignUser->fresh()->is_active);
    }

    public function test_management_pages_and_scoped_csv_exports_render(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();
        $branch = $company->branches()->firstOrFail();
        $contact = $company->contacts()->create([
            'branch_id' => $branch->id, 'name' => 'Contacto Visible', 'status' => 'prospect',
        ]);
        $opportunity = $contact->opportunities()->create([
            'company_id' => $company->id, 'branch_id' => $branch->id,
            'title' => 'Venta Visible', 'stage' => 'new', 'amount_cents' => 10000,
        ]);

        $this->actingAs($super)->get('/dashboard')->assertOk()->assertSee('Pepsi');
        $this->actingAs($super)->get('/companies/'.$company->id)->assertOk()->assertSee('Administradores de empresa');
        $this->actingAs($super)->get('/companies/'.$company->id.'/edit')->assertOk();
        $this->actingAs($super)->get('/companies/'.$company->id.'/admins/create')->assertOk();
        $this->actingAs($super)->get('/companies/'.$company->id.'/admins/'.$admin->id.'/edit')->assertOk();

        $this->actingAs($admin)->get('/dashboard')->assertOk()->assertSee('Contactos');
        $this->actingAs($admin)->get('/branches/'.$branch->id.'/edit')->assertOk();
        $this->actingAs($admin)->get('/contacts/create')->assertOk();
        $this->actingAs($admin)->get('/contacts/'.$contact->id.'/edit')->assertOk();
        $this->actingAs($admin)->get('/opportunities/create?contact='.$contact->id)->assertOk();
        $this->actingAs($admin)->get('/opportunities/'.$opportunity->id.'/edit')->assertOk();
        $this->actingAs($admin)->get('/activity')->assertOk();
        $contactsCsv = $this->actingAs($admin)->get('/contacts/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->streamedContent();
        $opportunitiesCsv = $this->actingAs($admin)->get('/opportunities/export')->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8')->streamedContent();
        $this->assertStringContainsString('Contacto Visible', $contactsCsv);
        $this->assertStringContainsString('Venta Visible', $opportunitiesCsv);
        $this->assertStringContainsString('GTQ', $opportunitiesCsv);

        for ($i = 0; $i < 16; $i++) {
            $company->contacts()->create(['branch_id' => $branch->id, 'name' => 'Paginado '.$i, 'status' => 'prospect']);
        }
        $this->actingAs($admin)->get('/contacts')->assertOk()->assertSee('Siguiente');
    }

    public function test_password_recovery_uses_single_use_token(): void
    {
        Notification::fake();
        $user = User::factory()->create(['role' => 'super_admin', 'password' => 'old-password-123']);

        $this->post('/forgot-password', ['email' => $user->email])->assertSessionHas('status');
        Notification::assertSentTo($user, ResetPassword::class);
        $this->post('/forgot-password', ['email' => 'unknown@example.test'])->assertSessionHas('status');
        $token = Password::createToken($user);

        $payload = [
            'token' => $token, 'email' => $user->email,
            'password' => 'new-password-456', 'password_confirmation' => 'new-password-456',
        ];
        $this->post('/reset-password', $payload)->assertRedirect('/login');
        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
        $this->post('/reset-password', $payload)->assertSessionHasErrors('email');
    }

    public function test_central_branch_and_last_company_administrator_are_protected(): void
    {
        $super = User::factory()->create(['role' => 'super_admin']);
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();
        $central = $company->branches()->firstOrFail();

        $this->actingAs($admin)->put('/branches/'.$central->id, ['name' => 'Nueva central'])
            ->assertSessionHasErrors('name');
        $this->assertSame('Central', $central->fresh()->name);
        $this->actingAs($super)->patch('/companies/'.$company->id.'/admins/'.$admin->id.'/status', ['is_active' => '0'])
            ->assertSessionHasErrors('administrator');
        $this->assertTrue($admin->fresh()->is_active);

        $north = $company->branches()->create(['name' => 'Norte']);
        $admin->update(['branch_id' => $north->id]);
        $this->actingAs($admin->fresh())->patch('/branches/'.$north->id.'/status', ['is_active' => '0'])
            ->assertSessionHasErrors('branch');
        $this->assertTrue($north->fresh()->is_active);
    }

    public function test_moving_contact_moves_its_opportunities_to_the_same_branch(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();
        $central = $company->branches()->firstOrFail();
        $north = $company->branches()->create(['name' => 'Norte']);
        $contact = $company->contacts()->create(['branch_id' => $central->id, 'name' => 'Cliente', 'status' => 'customer']);
        $opportunity = $contact->opportunities()->create([
            'company_id' => $company->id, 'branch_id' => $central->id,
            'title' => 'Renovación', 'stage' => 'proposal', 'amount_cents' => 10000,
        ]);

        $this->actingAs($admin)->put('/contacts/'.$contact->id, [
            'name' => 'Cliente', 'status' => 'customer', 'branch_id' => $north->id,
        ])->assertRedirect('/contacts/'.$contact->id);
        $this->assertSame($north->id, $contact->fresh()->branch_id);
        $this->assertSame($north->id, $opportunity->fresh()->branch_id);
    }

    public function test_export_protects_spreadsheet_formulas(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();
        $company->contacts()->create([
            'branch_id' => $company->branches()->firstOrFail()->id,
            'name' => '=2+2', 'status' => 'prospect',
        ]);

        $csv = $this->actingAs($admin)->get('/contacts/export')->streamedContent();
        $this->assertStringContainsString("'=2+2", $csv);
    }
}
