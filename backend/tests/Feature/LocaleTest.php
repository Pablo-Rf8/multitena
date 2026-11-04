<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\CreateCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LocaleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_switch_between_spanish_and_english(): void
    {
        $this->get('/login')->assertOk()->assertSee('<html lang="es">', false)->assertSee('Elegí tu empresa')
            ->assertSee('data-language-picker', false)->assertSee('>Idioma</span>', false);

        $this->post('/locale', ['locale' => 'en'])->assertRedirect();
        $this->get('/login')->assertOk()->assertSee('<html lang="en">', false)->assertSee('Choose your company')->assertSee('Company code or work email')
            ->assertSee('>Language</span>', false)->assertSee('aria-label="English"', false)->assertSee('aria-current="true"', false);

        $this->post('/locale', ['locale' => 'es'])->assertRedirect();
        $this->get('/login')->assertOk()->assertSee('<html lang="es">', false)->assertSee('Elegí tu empresa');
    }

    public function test_invalid_locale_is_rejected_without_changing_selection(): void
    {
        $this->post('/locale', ['locale' => 'en'])->assertRedirect();
        $this->post('/locale', ['locale' => 'fr'])->assertSessionHasErrors('locale');
        $this->get('/login')->assertOk()->assertSee('<html lang="en">', false);
    }

    public function test_language_survives_login_and_logout_without_changing_company_scope(): void
    {
        $pepsi = app(CreateCompany::class)->handle('Pepsi (DEMO)', 'admin@pepsi.test', 'correct-password-123', true);
        app(CreateCompany::class)->handle('Coca-Cola (DEMO)', 'admin@coca.test', 'correct-password-123', true);

        $this->post('/locale', ['locale' => 'en']);
        $this->post('/login/company', ['company_code' => $pepsi->code]);
        $this->post('/login', ['email' => 'admin@pepsi.test', 'password' => 'correct-password-123'])->assertRedirect('/dashboard');
        $this->get('/dashboard')->assertOk()->assertSee('Workspace')->assertSee('Pepsi (DEMO)')->assertDontSee('Coca-Cola (DEMO)')
            ->assertSee('data-language-picker', false);
        $this->post('/logout')->assertRedirect('/login');
        $this->get('/login')->assertOk()->assertSee('<html lang="en">', false)->assertSee('Email address');

        $super = User::factory()->create(['role' => 'super_admin', 'company_id' => null, 'branch_id' => null]);
        $this->actingAs($super)->get('/dashboard')->assertOk()->assertSee('Companies')->assertSee('theme-platform')->assertDontSee('theme-tenant');
    }
}
