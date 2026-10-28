<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class TenantManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_admin_adds_branch_and_admin_only_to_own_company(): void
    {
        $creator = app(CreateCompany::class);
        $pepsi = $creator->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $coca = $creator->handle('Coca-Cola', 'admin@coca.test', 'correct-horse-battery');
        $admin = $pepsi->users()->firstOrFail();

        $this->actingAs($admin)->post('/branches', ['name' => 'Norte'])->assertRedirect('/dashboard');
        $branch = $pepsi->branches()->where('name', 'Norte')->firstOrFail();
        $this->assertFalse($branch->is_central);
        $this->assertDatabaseMissing('branches', ['company_id' => $coca->id, 'name' => 'Norte']);

        $this->actingAs($admin)->post('/branch-users', [
            'name' => 'Ana', 'email' => 'ana@pepsi.test', 'branch_id' => $branch->id,
            'password' => 'another-secure-password',
            'password_confirmation' => 'another-secure-password',
        ])->assertRedirect('/dashboard');
        $this->assertDatabaseHas('users', [
            'email' => 'ana@pepsi.test', 'role' => 'branch_admin',
            'company_id' => $pepsi->id, 'branch_id' => $branch->id,
        ]);

        $this->actingAs($admin)->post('/branch-users', [
            'name' => 'Incorrecto', 'email' => 'wrong@pepsi.test',
            'branch_id' => $coca->branches()->firstOrFail()->id,
            'password' => 'another-secure-password',
            'password_confirmation' => 'another-secure-password',
        ])->assertSessionHasErrors('branch_id');
        $this->assertDatabaseMissing('users', ['email' => 'wrong@pepsi.test']);
    }

    public function test_branch_admin_cannot_manage_tenant_or_see_other_branches(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $north = $company->branches()->create(['name' => 'Norte']);
        $company->branches()->create(['name' => 'Sur']);
        $branchAdmin = $company->users()->create([
            'name' => 'Ana', 'email' => 'ana@pepsi.test', 'password' => 'another-secure-password',
            'role' => 'branch_admin', 'branch_id' => $north->id,
        ]);

        $this->actingAs($branchAdmin)->get('/dashboard')
            ->assertOk()->assertSee('Norte')->assertDontSee('Sur')->assertDontSee('Agregar sucursal');
        $this->actingAs($branchAdmin)->post('/branches', ['name' => 'Oeste'])->assertForbidden();
        $this->actingAs($branchAdmin)->get('/branch-users/create')->assertForbidden();
        $this->assertDatabaseMissing('branches', ['name' => 'Oeste']);
    }

    public function test_user_can_change_password_only_with_current_password(): void
    {
        $user = User::factory()->create(['role' => 'super_admin', 'password' => 'old-password-123']);

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'incorrect', 'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('old-password-123', $user->fresh()->password));

        $this->actingAs($user)->put('/account/password', [
            'current_password' => 'old-password-123', 'password' => 'new-password-456',
            'password_confirmation' => 'new-password-456',
        ])->assertRedirect('/dashboard');
        $this->assertTrue(Hash::check('new-password-456', $user->fresh()->password));
    }

    public function test_demo_command_is_labeled_and_does_not_overwrite_existing_records(): void
    {
        $this->artisan('app:seed-demo')->assertSuccessful();
        $this->assertDatabaseCount('companies', 2);
        $this->assertDatabaseCount('branches', 5);
        $this->assertDatabaseCount('users', 4);
        $this->assertTrue(Company::where('slug', 'pepsi-demo')->firstOrFail()->is_demo);

        $this->artisan('app:seed-demo')->assertSuccessful();
        $this->assertDatabaseCount('companies', 2);
        $this->assertDatabaseCount('branches', 5);
        $this->assertDatabaseCount('users', 4);
    }
}
