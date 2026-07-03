<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\User;
use Tests\TestCase;

class AuthTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_02_142752_create_personal_access_tokens_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_03_000001_add_username_to_users_table.php']);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_03_000001_add_username_to_users_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_02_142752_create_personal_access_tokens_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        parent::tearDown();
    }

    private function createUser(array $overrides = []): User
    {
        return User::create(array_merge([
            'name' => 'Test User',
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
        ], $overrides));
    }

    public function test_login_with_valid_credentials_returns_token(): void
    {
        $this->createUser(['username' => 'admin']);

        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'password',
        ]);

        $response->assertOk()
            ->assertJsonStructure([
                'token',
                'user' => ['name', 'username', 'email', 'role'],
            ]);
    }

    public function test_login_with_invalid_credentials_returns_401(): void
    {
        $this->createUser(['username' => 'admin']);

        $response = $this->postJson('/api/login', [
            'username' => 'admin',
            'password' => 'wrong-password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_with_nonexistent_username_returns_401(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'nonexistent',
            'password' => 'password',
        ]);

        $response->assertUnauthorized();
    }

    public function test_login_validates_required_username(): void
    {
        $response = $this->postJson('/api/login', [
            'password' => 'password',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['username']);
    }

    public function test_login_validates_required_password(): void
    {
        $response = $this->postJson('/api/login', [
            'username' => 'admin',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['password']);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/me');

        $response->assertOk()
            ->assertJson([
                'user' => [
                    'name' => $user->name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'role' => 'admin',
                ],
            ]);
    }

    public function test_me_without_token_returns_401(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_me_with_invalid_token_returns_401(): void
    {
        $response = $this->withToken('invalid-token')->getJson('/api/me');

        $response->assertUnauthorized();
    }

    public function test_logout_revokes_token(): void
    {
        $user = $this->createUser();
        $token = $user->createToken('auth-token')->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/logout');

        $response->assertOk()
            ->assertJson([
                'message' => 'Sesión cerrada exitosamente',
            ]);

        // Verify token was deleted from the database
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_logout_without_token_returns_401(): void
    {
        $response = $this->postJson('/api/logout');

        $response->assertUnauthorized();
    }
}
