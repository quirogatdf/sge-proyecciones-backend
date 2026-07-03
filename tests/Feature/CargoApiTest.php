<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Cargo;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CargoApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    }

    public function test_index_returns_all_cargos(): void
    {
        Cargo::factory()->count(3)->create();

        $response = $this->getJson('/api/cargos');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_show_returns_single_cargo(): void
    {
        $cargo = Cargo::factory()->create([
            'codigo' => '0001',
            'nombre' => 'Director',
            'descripcion' => 'Director de la institución',
        ]);

        $response = $this->getJson("/api/cargos/{$cargo->id}");

        $response->assertOk()
            ->assertJsonPath('data.codigo', '0001')
            ->assertJsonPath('data.nombre', 'Director')
            ->assertJsonPath('data.descripcion', 'Director de la institución');
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/cargos/999');

        $response->assertNotFound();
    }

    public function test_store_creates_cargo(): void
    {
        $response = $this->postJson('/api/cargos', [
            'codigo' => '0002',
            'nombre' => 'Secretario',
            'descripcion' => 'Secretario administrativo',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.codigo', '0002')
            ->assertJsonPath('data.nombre', 'Secretario')
            ->assertJsonPath('data.descripcion', 'Secretario administrativo');

        $this->assertDatabaseHas('cargos', [
            'codigo' => '0002',
            'nombre' => 'Secretario',
        ]);
    }

    public function test_store_validates_required_codigo(): void
    {
        $response = $this->postJson('/api/cargos', [
            'nombre' => 'Tesorero',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo']);
    }

    public function test_store_validates_required_nombre(): void
    {
        $response = $this->postJson('/api/cargos', [
            'codigo' => '0003',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_store_validates_unique_codigo(): void
    {
        Cargo::factory()->create(['codigo' => '0004']);

        $response = $this->postJson('/api/cargos', [
            'codigo' => '0004',
            'nombre' => 'Nuevo cargo',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo']);
    }

    public function test_store_allows_nullable_descripcion(): void
    {
        $response = $this->postJson('/api/cargos', [
            'codigo' => '0005',
            'nombre' => 'Vocal',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.codigo', '0005')
            ->assertJsonPath('data.nombre', 'Vocal')
            ->assertJsonPath('data.descripcion', null);
    }

    public function test_update_modifies_cargo(): void
    {
        $cargo = Cargo::factory()->create([
            'nombre' => 'Original',
        ]);

        $response = $this->putJson("/api/cargos/{$cargo->id}", [
            'nombre' => 'Modificado',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nombre', 'Modificado');

        $this->assertDatabaseHas('cargos', ['nombre' => 'Modificado']);
    }

    public function test_update_validates_unique_codigo_on_other_record(): void
    {
        $cargo1 = Cargo::factory()->create(['codigo' => '0006']);
        $cargo2 = Cargo::factory()->create(['codigo' => '0007']);

        $response = $this->putJson("/api/cargos/{$cargo1->id}", [
            'codigo' => '0007',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo']);
    }

    public function test_update_validates_codigo_max_length(): void
    {
        $cargo = Cargo::factory()->create();

        $response = $this->putJson("/api/cargos/{$cargo->id}", [
            'codigo' => '12345',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['codigo']);
    }

    public function test_destroy_deletes_cargo(): void
    {
        $cargo = Cargo::factory()->create();

        $response = $this->deleteJson("/api/cargos/{$cargo->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('cargos', ['id' => $cargo->id]);
    }
}