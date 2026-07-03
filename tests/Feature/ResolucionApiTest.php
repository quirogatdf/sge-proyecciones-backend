<?php

namespace Tests\Feature;

use App\Enums\RolUsuario;
use App\Models\Resolucion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResolucionApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    }

    public function test_index_returns_all_resoluciones(): void
    {
        Resolucion::factory()->count(3)->create();

        $response = $this->getJson('/api/resoluciones');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_show_returns_single_resolucion(): void
    {
        $resolucion = Resolucion::factory()->create([
            'nombre' => 'Resolución Test',
            'año' => 2025,
            'observacion' => 'Observación de prueba',
            'url' => 'https://example.com/resolucion',
        ]);

        $response = $this->getJson("/api/resoluciones/{$resolucion->id}");

        $response->assertOk()
            ->assertJsonPath('data.nombre', 'Resolución Test')
            ->assertJsonPath('data.año', 2025)
            ->assertJsonPath('data.observacion', 'Observación de prueba')
            ->assertJsonPath('data.url', 'https://example.com/resolucion');
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/resoluciones/999');

        $response->assertNotFound();
    }

    public function test_store_creates_resolucion(): void
    {
        $response = $this->postJson('/api/resoluciones', [
            'nombre' => 'Resolución Nueva',
            'año' => 2025,
            'observacion' => 'Una observación',
            'url' => 'https://example.com/doc',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Resolución Nueva')
            ->assertJsonPath('data.año', 2025)
            ->assertJsonPath('data.observacion', 'Una observación')
            ->assertJsonPath('data.url', 'https://example.com/doc');

        $this->assertDatabaseHas('resoluciones', [
            'nombre' => 'Resolución Nueva',
            'año' => 2025,
        ]);
    }

    public function test_store_validates_required_nombre(): void
    {
        $response = $this->postJson('/api/resoluciones', [
            'año' => 2025,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_store_validates_required_año(): void
    {
        $response = $this->postJson('/api/resoluciones', [
            'nombre' => 'Resolución sin año',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['año']);
    }

    public function test_store_validates_año_is_integer(): void
    {
        $response = $this->postJson('/api/resoluciones', [
            'nombre' => 'Resolución',
            'año' => 'no-es-numero',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['año']);
    }

    public function test_store_allows_nullable_observacion_and_url(): void
    {
        $response = $this->postJson('/api/resoluciones', [
            'nombre' => 'Resolución Básica',
            'año' => 2025,
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Resolución Básica')
            ->assertJsonPath('data.año', 2025)
            ->assertJsonPath('data.observacion', null)
            ->assertJsonPath('data.url', null);
    }

    public function test_update_modifies_resolucion(): void
    {
        $resolucion = Resolucion::factory()->create([
            'nombre' => 'Original',
        ]);

        $response = $this->putJson("/api/resoluciones/{$resolucion->id}", [
            'nombre' => 'Modificado',
            'año' => 2026,
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nombre', 'Modificado')
            ->assertJsonPath('data.año', 2026);

        $this->assertDatabaseHas('resoluciones', ['nombre' => 'Modificado']);
    }

    public function test_destroy_deletes_resolucion(): void
    {
        $resolucion = Resolucion::factory()->create();

        $response = $this->deleteJson("/api/resoluciones/{$resolucion->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('resoluciones', ['id' => $resolucion->id]);
    }
}
