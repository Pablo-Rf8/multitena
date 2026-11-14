<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InterfaceNavigationTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_pages_separate_team_access_and_branches(): void
    {
        $company = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $admin = $company->users()->firstOrFail();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee(route('branches.index'))->assertSee(route('team.index'))
            ->assertSee(route('assets.page.css', ['name' => 'companies-dashboard']));
        $this->get(route('branches.index'))->assertOk()->assertSee('Central')
            ->assertSee(route('branches.create'));
        $this->get(route('team.index'))->assertOk()->assertSee($admin->email)
            ->assertSee(route('branch-users.create'));
        $this->get(route('workforce.index'))->assertOk()->assertSee('Registrar empleado')
            ->assertDontSee('Dominios de correo autorizados')
            ->assertSee(route('assets.page.css', ['name' => 'workforce-index']));
        $this->get(route('workforce.categories.index'))->assertOk()->assertSee('Crear categoría')
            ->assertSee(route('assets.page.css', ['name' => 'workforce-categories']));
        $this->get(route('assets.page.css', ['name' => 'companies-dashboard']))->assertOk();
        $this->get(route('assets.page.js', ['name' => 'credential-templates-form']))->assertOk();
        $this->get(route('credential-templates.create'))->assertOk()
            ->assertSee(route('assets.page.css', ['name' => 'credential-templates-form']))
            ->assertSee(route('assets.page.js', ['name' => 'credential-templates-form']));
        $this->get(route('workforce.settings.edit'))->assertOk()->assertSee($company->code);
        $this->get(route('workforce.settings.edit'))->assertSee(route('assets.page.css', ['name' => 'workforce-settings']));
        $this->get(route('workforce.employees.create'))->assertOk()
            ->assertSee(route('assets.page.css', ['name' => 'workforce-register']))
            ->assertSee('name="branch_id"', false);
        $this->get(route('workforce.devices.index'))->assertOk()->assertSee('Dispositivos');
        $this->get(route('workforce.sessions.index'))->assertOk()->assertSee('Sesiones web y móviles');

        $this->post(route('workforce.categories'), ['name' => 'Repartidor', 'permissions' => ['contacts.read']])
            ->assertRedirect();
        $this->assertDatabaseHas('categories', ['company_id' => $company->id, 'name' => 'Repartidor']);
        $this->get(route('workforce.categories.index'))->assertOk()->assertSee('Repartidor');
    }

    public function test_profile_is_personal_and_role_pages_stay_protected(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi', 'admin@pepsi.test', 'correct-horse-battery');
        $coca = app(CreateCompany::class)->handle('Coca Cola', 'admin@coca.test', 'correct-horse-battery');
        $admin = $pepsi->users()->firstOrFail();
        $employee = $pepsi->users()->create([
            'name' => 'Eva', 'email' => 'eva@pepsi.test', 'password' => 'correct-horse-battery',
            'role' => 'employee', 'branch_id' => $admin->branch_id, 'approval_status' => 'approved',
        ]);
        $category = Category::create(['company_id' => $pepsi->id, 'name' => 'Repartidor', 'permissions' => []]);
        $employee->update(['category_id' => $category->id]);

        $this->actingAs($employee)->get(route('profile.show'))->assertOk()
            ->assertSee('Mi perfil')->assertSee('Repartidor')->assertSee('Pepsi')
            ->assertSee(route('assets.page.css', ['name' => 'profile-show']))
            ->assertDontSee($coca->name);
        $this->get(route('profile.edit'))->assertOk()
            ->assertSee(route('assets.page.css', ['name' => 'profile-edit']))
            ->assertSee('name="phone"', false);
        $this->put(route('profile.update'), ['name' => 'Eva R.', 'phone' => '5555-1111'])
            ->assertRedirect(route('profile.show'));
        $this->assertDatabaseHas('users', ['id' => $employee->id, 'name' => 'Eva R.', 'phone' => '5555-1111']);
        $this->get(route('workforce.categories.index'))->assertForbidden();
        $this->get(route('team.index'))->assertForbidden();
        $this->get(route('companies.admins', $coca))->assertForbidden();

        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super)->get(route('profile.show'))->assertOk()->assertSee('Superadministrador');
        $this->get(route('companies.create'))->assertOk()
            ->assertSee(route('assets.page.js', ['name' => 'companies-create']));
        $this->get(route('companies.show', $pepsi))->assertOk()
            ->assertSee(route('companies.branches', $pepsi))
            ->assertSee(route('companies.admins', $pepsi))
            ->assertSee(route('companies.employees', $pepsi));
        $this->get(route('companies.branches', $pepsi))->assertOk()->assertSee('Central');
        $this->get(route('companies.admins', $pepsi))->assertOk()->assertSee($admin->email);
        $this->get(route('companies.activity', $pepsi))->assertOk();
    }
}
