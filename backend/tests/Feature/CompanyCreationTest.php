<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CompanyCreationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_creates_company_central_branch_and_admin_atomically(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);

        $this->actingAs($superAdmin)->post('/companies', [
            'name' => 'Pepsi',
            'admin_password' => 'correct-horse-battery',
            'admin_password_confirmation' => 'correct-horse-battery',
        ])->assertRedirect('/dashboard');

        $company = Company::with(['branches', 'users'])->firstOrFail();
        $this->assertSame('Pepsi', $company->name);
        $this->assertSame('pepsi', $company->slug);
        $this->assertCount(1, $company->branches);
        $this->assertSame('Central', $company->branches[0]->name);
        $this->assertTrue($company->branches[0]->is_central);
        $this->assertCount(1, $company->users);
        $this->assertSame('admin@pepsi.com', $company->users[0]->email);
        $this->assertSame('company_admin', $company->users[0]->role);
        $this->assertSame($company->branches[0]->id, $company->users[0]->branch_id);
        $this->assertTrue(Hash::check('correct-horse-battery', $company->users[0]->password));
    }

    public function test_company_admin_cannot_create_another_company(): void
    {
        $admin = User::factory()->create(['role' => 'company_admin']);

        $this->actingAs($admin)->get('/companies/create')->assertForbidden();
        $this->actingAs($admin)->post('/companies', [
            'name' => 'Pepsi',
            'admin_password' => 'correct-horse-battery',
            'admin_password_confirmation' => 'correct-horse-battery',
        ])->assertForbidden();
        $this->assertDatabaseCount('companies', 0);
    }

    public function test_duplicate_company_does_not_add_branches_or_users(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $payload = [
            'name' => 'Pepsi',
            'admin_password' => 'correct-horse-battery',
            'admin_password_confirmation' => 'correct-horse-battery',
        ];

        $this->actingAs($superAdmin)->post('/companies', $payload)->assertRedirect();
        $this->actingAs($superAdmin)->post('/companies', $payload)->assertSessionHasErrors('name');

        $this->assertDatabaseCount('companies', 1);
        $this->assertDatabaseCount('branches', 1);
        $this->assertDatabaseCount('users', 2);
    }

    public function test_admin_only_sees_own_company(): void
    {
        $superAdmin = User::factory()->create(['role' => 'super_admin']);
        $payload = fn (string $name) => [
            'name' => $name,
            'admin_password' => 'correct-horse-battery',
            'admin_password_confirmation' => 'correct-horse-battery',
        ];
        $this->actingAs($superAdmin)->post('/companies', $payload('Pepsi'));
        $this->actingAs($superAdmin)->post('/companies', $payload('Coca Cola'));

        $pepsiAdmin = User::where('email', 'admin@pepsi.com')->firstOrFail();
        $this->post('/logout')->assertRedirect('/login');
        $this->post('/login', [
            'email' => $pepsiAdmin->email,
            'password' => 'correct-horse-battery',
        ])->assertRedirect('/dashboard');
        $this->get('/dashboard')
            ->assertOk()->assertSee('Pepsi')->assertDontSee('Coca Cola');
    }
}
