<?php

namespace Tests\Feature;

use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NivelApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_niveles(): void
    {
        Nivel::factory()->count(3)->create();

        $response = $this->getJson('/api/niveles');

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_show_returns_single_nivel(): void
    {
        $nivel = Nivel::factory()->create([
            'nombre' => 'Primario',
            'sigla' => 'PRI',
        ]);

        $response = $this->getJson("/api/niveles/{$nivel->id}");

        $response->assertOk()
            ->assertJsonPath('data.nombre', 'Primario')
            ->assertJsonPath('data.sigla', 'PRI');
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $response = $this->getJson('/api/niveles/999');

        $response->assertNotFound();
    }

    public function test_store_creates_nivel(): void
    {
        $response = $this->postJson('/api/niveles', [
            'nombre' => 'Secundario',
            'sigla' => 'SEC',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Secundario')
            ->assertJsonPath('data.sigla', 'SEC');

        $this->assertDatabaseHas('niveles', [
            'nombre' => 'Secundario',
            'sigla' => 'SEC',
        ]);
    }

    public function test_store_validates_required_nombre(): void
    {
        $response = $this->postJson('/api/niveles', [
            'sigla' => 'SEC',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_store_allows_nullable_sigla(): void
    {
        $response = $this->postJson('/api/niveles', [
            'nombre' => 'Terciario',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.nombre', 'Terciario')
            ->assertJsonPath('data.sigla', null);
    }

    public function test_update_modifies_nivel(): void
    {
        $nivel = Nivel::factory()->create(['nombre' => 'Original']);

        $response = $this->putJson("/api/niveles/{$nivel->id}", [
            'nombre' => 'Modificado',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.nombre', 'Modificado');

        $this->assertDatabaseHas('niveles', ['nombre' => 'Modificado']);
    }

    public function test_update_validates_nombre_max_length(): void
    {
        $nivel = Nivel::factory()->create();

        $response = $this->putJson("/api/niveles/{$nivel->id}", [
            'nombre' => str_repeat('a', 256),
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['nombre']);
    }

    public function test_destroy_deletes_nivel(): void
    {
        $nivel = Nivel::factory()->create();

        $response = $this->deleteJson("/api/niveles/{$nivel->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('niveles', ['id' => $nivel->id]);
    }
}