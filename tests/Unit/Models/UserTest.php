<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\RolUsuario;
use App\Models\User;
use Laravel\Sanctum\HasApiTokens;
use Tests\TestCase;

class UserTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        parent::tearDown();
    }

    public function test_is_admin_returns_true_when_role_is_admin(): void
    {
        $user = User::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->assertTrue($user->isAdmin());
    }

    public function test_is_admin_returns_false_when_role_is_guest(): void
    {
        $user = User::create([
            'name' => 'Guest User',
            'email' => 'guest@example.com',
            'password' => bcrypt('password'),
            'role' => 'guest',
        ]);

        $this->assertFalse($user->isAdmin());
    }

    public function test_role_casts_admin_to_rol_usuario_enum(): void
    {
        $user = User::create([
            'name' => 'Admin Enum User',
            'email' => 'admin-enum@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);

        $this->assertInstanceOf(RolUsuario::class, $user->role);
        $this->assertEquals(RolUsuario::Admin, $user->role);
    }

    public function test_role_casts_guest_to_rol_usuario_enum(): void
    {
        $user = User::create([
            'name' => 'Guest Enum User',
            'email' => 'guest-enum@example.com',
            'password' => bcrypt('password'),
            'role' => 'guest',
        ]);

        $this->assertInstanceOf(RolUsuario::class, $user->role);
        $this->assertEquals(RolUsuario::Guest, $user->role);
    }

    public function test_role_is_in_fillable(): void
    {
        $user = new User();

        $this->assertContains('role', $user->getFillable());
    }

    public function test_model_uses_has_api_tokens_trait(): void
    {
        $traits = class_uses(User::class);

        $this->assertContains(HasApiTokens::class, $traits);
    }
}
