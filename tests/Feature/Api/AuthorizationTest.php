<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class AuthorizationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_03_000001_add_username_to_users_table.php']);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_03_000001_add_username_to_users_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        parent::tearDown();
    }

    public function test_admin_allows_all_abilities(): void
    {
        $admin = $this->makeAdmin();

        $this->assertTrue(Gate::forUser($admin)->allows('viewAny', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('view', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('create', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('update', User::class));
        $this->assertTrue(Gate::forUser($admin)->allows('delete', User::class));
    }

    public function test_guest_denies_mutating_abilities(): void
    {
        $guest = $this->makeGuest();

        $this->assertFalse(Gate::forUser($guest)->allows('create', User::class));
        $this->assertFalse(Gate::forUser($guest)->allows('update', User::class));
        $this->assertFalse(Gate::forUser($guest)->allows('delete', User::class));
    }

    public function test_guest_can_view_any_and_view(): void
    {
        // Gate::before returns true for guest + viewAny/view.
        // Guest users are allowed to view and list resources.
        $guest = $this->makeGuest();

        $result = Gate::forUser($guest)->inspect('viewAny', User::class);
        $this->assertTrue($result->allowed());

        $result = Gate::forUser($guest)->inspect('view', User::class);
        $this->assertTrue($result->allowed());
    }

    private function makeAdmin(): User
    {
        return User::factory()->create([
            'name' => 'Admin Auth Test',
            'email' => 'admin-auth-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ]);
    }

    private function makeGuest(): User
    {
        return User::factory()->create([
            'name' => 'Guest Auth Test',
            'email' => 'guest-auth-test@example.com',
            'password' => bcrypt('password'),
            'role' => 'guest',
        ]);
    }
}
