<?php

namespace Tests\Feature;

use App\Models\Company;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublishedDemoAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_production_demo_access_is_created_and_reset_without_duplication(): void
    {
        $super = User::factory()->create([
            'email' => 'superadmin@multitena.local', 'role' => 'super_admin',
            'company_id' => null, 'branch_id' => null, 'password' => 'old-password-123',
        ]);
        putenv('DEMO_ACCESS_RESET_JSON='.json_encode([
            'superadmin@multitena.local' => 'Platform!Sample!2026',
            'admin@pepsi-demo.test' => 'Pepsi!Sample!2026',
            'admin@coca-cola-demo.test' => 'Coca!Sample!2026',
        ]));

        try {
            $this->artisan('app:reset-published-demo-access')->assertSuccessful();
            $this->assertTrue(Hash::check('Platform!Sample!2026', $super->fresh()->password));
            $this->assertTrue(Hash::check('Pepsi!Sample!2026', User::where('email', 'admin@pepsi-demo.test')->firstOrFail()->password));
            $this->assertTrue(Hash::check('Coca!Sample!2026', User::where('email', 'admin@coca-cola-demo.test')->firstOrFail()->password));
            $this->assertSame(2, Company::where('is_demo', true)->count());
            $this->artisan('app:reset-published-demo-access')->assertSuccessful();
            $this->assertSame(3, User::count());
        } finally {
            putenv('DEMO_ACCESS_RESET_JSON');
        }
    }
}
