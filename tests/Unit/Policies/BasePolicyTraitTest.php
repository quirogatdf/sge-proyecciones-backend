<?php

declare(strict_types=1);

namespace Tests\Unit\Policies;

use App\Enums\RolUsuario;
use App\Models\User;
use App\Policies\BasePolicyTrait;
use Tests\TestCase;

class BasePolicyTraitTest extends TestCase
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

    public function test_admin_returns_true_for_view_any(): void
    {
        $policy = $this->makePolicy();
        $admin = $this->makeAdmin();

        $result = $policy->before($admin, 'viewAny');

        $this->assertTrue($result);
    }

    public function test_admin_returns_true_for_view(): void
    {
        $policy = $this->makePolicy();
        $admin = $this->makeAdmin();

        $result = $policy->before($admin, 'view');

        $this->assertTrue($result);
    }

    public function test_admin_returns_true_for_create(): void
    {
        $policy = $this->makePolicy();
        $admin = $this->makeAdmin();

        $result = $policy->before($admin, 'create');

        $this->assertTrue($result);
    }

    public function test_admin_returns_true_for_update(): void
    {
        $policy = $this->makePolicy();
        $admin = $this->makeAdmin();

        $result = $policy->before($admin, 'update');

        $this->assertTrue($result);
    }

    public function test_admin_returns_true_for_delete(): void
    {
        $policy = $this->makePolicy();
        $admin = $this->makeAdmin();

        $result = $policy->before($admin, 'delete');

        $this->assertTrue($result);
    }

    public function test_guest_returns_null_for_view_any(): void
    {
        $policy = $this->makePolicy();
        $guest = $this->makeGuest();

        $result = $policy->before($guest, 'viewAny');

        $this->assertNull($result);
    }

    public function test_guest_returns_null_for_view(): void
    {
        $policy = $this->makePolicy();
        $guest = $this->makeGuest();

        $result = $policy->before($guest, 'view');

        $this->assertNull($result);
    }

    public function test_guest_returns_false_for_create(): void
    {
        $policy = $this->makePolicy();
        $guest = $this->makeGuest();

        $result = $policy->before($guest, 'create');

        $this->assertFalse($result);
    }

    public function test_guest_returns_false_for_update(): void
    {
        $policy = $this->makePolicy();
        $guest = $this->makeGuest();

        $result = $policy->before($guest, 'update');

        $this->assertFalse($result);
    }

    public function test_guest_returns_false_for_delete(): void
    {
        $policy = $this->makePolicy();
        $guest = $this->makeGuest();

        $result = $policy->before($guest, 'delete');

        $this->assertFalse($result);
    }

    public function test_guest_returns_false_for_restore(): void
    {
        $policy = $this->makePolicy();
        $guest = $this->makeGuest();

        $result = $policy->before($guest, 'restore');

        $this->assertFalse($result);
    }

    public function test_guest_returns_false_for_force_delete(): void
    {
        $policy = $this->makePolicy();
        $guest = $this->makeGuest();

        $result = $policy->before($guest, 'forceDelete');

        $this->assertFalse($result);
    }

    private function makePolicy(): object
    {
        return new class
        {
            use BasePolicyTrait;
        };
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'Admin',
            'email' => 'admin-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function makeGuest(): User
    {
        return User::create([
            'name' => 'Guest',
            'email' => 'guest-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'guest',
        ]);
    }
}
