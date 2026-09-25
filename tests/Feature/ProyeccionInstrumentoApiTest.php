<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProyeccionInstrumentoApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    }

    public function test_index_returns_instrumentos_ordered_by_anio_desc(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2024']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2025']);

        $response = $this->getJson("/api/proyecciones/{$proyeccion->id}/instrumentos");

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonPath('data.0.anio', '2026')
            ->assertJsonPath('data.1.anio', '2025')
            ->assertJsonPath('data.2.anio', '2024');
    }

    public function test_index_returns_snapshot_con_relaciones(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $instrumento = ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
        ]);

        $response = $this->getJson("/api/proyecciones/{$proyeccion->id}/instrumentos");

        $response->assertOk()
            ->assertJsonPath('data.0.resolucion.nombre', $instrumento->resolucion->nombre)
            ->assertJsonPath('data.0.cargo.nombre', $instrumento->cargo->nombre)
            ->assertJsonPath('data.0.funcion.nombre', $instrumento->funcion->nombre)
            ->assertJsonPath('data.0.turno.nombre', $instrumento->turno->nombre);
    }

    public function test_index_returns_empty_when_sin_historial(): void
    {
        $proyeccion = Proyeccion::factory()->create();

        $response = $this->getJson("/api/proyecciones/{$proyeccion->id}/instrumentos");

        $response->assertOk()
            ->assertJsonCount(0, 'data');
    }

    public function test_store_creates_instrumento(): void
    {
        $proyeccion = Proyeccion::factory()->create();

        $response = $this->postJson("/api/proyecciones/{$proyeccion->id}/instrumentos", [
            'anio' => '2027',
            'orden' => 12,
            'destino_nuevo' => 'Juzgado Civil N° 1',
            'observaciones' => 'Continuidad 2027',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.anio', '2027')
            ->assertJsonPath('data.orden', 12)
            ->assertJsonPath('data.destino_nuevo', 'Juzgado Civil N° 1')
            ->assertJsonPath('data.observaciones', 'Continuidad 2027');

        $this->assertDatabaseHas('proyeccion_instrumentos', [
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2027',
        ]);
    }

    public function test_store_validates_required_anio(): void
    {
        $proyeccion = Proyeccion::factory()->create();

        $response = $this->postJson("/api/proyecciones/{$proyeccion->id}/instrumentos", []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['anio']);
    }

    public function test_store_valida_anio_duplicado_por_proyeccion(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2027']);

        $response = $this->postJson("/api/proyecciones/{$proyeccion->id}/instrumentos", [
            'anio' => '2027',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['anio']);
    }

    public function test_store_permite_mismo_anio_en_otra_proyeccion(): void
    {
        $p1 = Proyeccion::factory()->create();
        $p2 = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $p1->id, 'anio' => '2027']);

        $response = $this->postJson("/api/proyecciones/{$p2->id}/instrumentos", [
            'anio' => '2027',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.anio', '2027');
    }

    public function test_store_returns_404_for_nonexistent_proyeccion(): void
    {
        $response = $this->postJson('/api/proyecciones/999/instrumentos', [
            'anio' => '2027',
        ]);

        $response->assertNotFound();
    }

    public function test_update_modifica_un_instrumento_del_historial(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $instrumento = ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
            'estado' => 'Pendiente',
        ]);

        $response = $this->putJson("/api/proyecciones/{$proyeccion->id}/instrumentos/{$instrumento->id}", [
            'estado' => 'Autorizado',
            'motivo' => 'Continuidad',
            'n_expediente' => 'EXP-123',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.estado', 'Autorizado')
            ->assertJsonPath('data.motivo', 'Continuidad')
            ->assertJsonPath('data.n_expediente', 'EXP-123');

        $this->assertDatabaseHas('proyeccion_instrumentos', [
            'id' => $instrumento->id,
            'estado' => 'Autorizado',
        ]);
    }

    public function test_update_permite_completar_un_instrumento_creado_en_blanco(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $instrumento = ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2027',
            'estado' => null,
            'motivo' => null,
        ]);

        $response = $this->putJson("/api/proyecciones/{$proyeccion->id}/instrumentos/{$instrumento->id}", [
            'estado' => 'Pendiente',
            'motivo' => 'Sin definir',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.estado', 'Pendiente')
            ->assertJsonPath('data.motivo', 'Sin definir');
    }

    public function test_update_valida_anio_duplicado_ignorando_el_propio(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2025']);
        $instrumento = ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        // Mantener el propio año no debe romper la unicidad
        $this->putJson("/api/proyecciones/{$proyeccion->id}/instrumentos/{$instrumento->id}", ['anio' => '2026'])
            ->assertOk();

        // Chocar con otro año de la misma proyección -> 422
        $this->putJson("/api/proyecciones/{$proyeccion->id}/instrumentos/{$instrumento->id}", ['anio' => '2025'])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['anio']);
    }

    public function test_update_returns_404_si_el_instrumento_no_pertenece_a_la_proyeccion(): void
    {
        $p1 = Proyeccion::factory()->create();
        $p2 = Proyeccion::factory()->create();
        $instrumento = ProyeccionInstrumento::factory()->create(['proyeccion_id' => $p1->id, 'anio' => '2026']);

        $this->putJson("/api/proyecciones/{$p2->id}/instrumentos/{$instrumento->id}", ['estado' => 'Autorizado'])
            ->assertNotFound();
    }

    public function test_update_returns_404_for_nonexistent_instrumento(): void
    {
        $proyeccion = Proyeccion::factory()->create();

        $this->putJson("/api/proyecciones/{$proyeccion->id}/instrumentos/999", ['estado' => 'Autorizado'])
            ->assertNotFound();
    }

    public function test_destroy_elimina_un_instrumento_del_historial(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2025']);
        $instrumento = ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $this->deleteJson("/api/proyecciones/{$proyeccion->id}/instrumentos/{$instrumento->id}")
            ->assertNoContent();

        $this->assertDatabaseMissing('proyeccion_instrumentos', ['id' => $instrumento->id]);
        $this->assertDatabaseHas('proyeccion_instrumentos', ['proyeccion_id' => $proyeccion->id, 'anio' => '2025']);
    }

    public function test_destroy_returns_404_si_el_instrumento_no_pertenece_a_la_proyeccion(): void
    {
        $p1 = Proyeccion::factory()->create();
        $p2 = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $p1->id, 'anio' => '2025']);
        $instrumento = ProyeccionInstrumento::factory()->create(['proyeccion_id' => $p1->id, 'anio' => '2026']);

        $this->deleteJson("/api/proyecciones/{$p2->id}/instrumentos/{$instrumento->id}")
            ->assertNotFound();

        $this->assertDatabaseHas('proyeccion_instrumentos', ['id' => $instrumento->id]);
    }

    public function test_destroy_returns_409_si_es_el_unico_instrumento_de_la_proyeccion(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $instrumento = ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $this->deleteJson("/api/proyecciones/{$proyeccion->id}/instrumentos/{$instrumento->id}")
            ->assertConflict()
            ->assertJsonPath('message', 'No se puede eliminar el último instrumento de la proyección.');

        $this->assertDatabaseHas('proyeccion_instrumentos', ['id' => $instrumento->id]);
    }

    public function test_destroy_returns_404_for_nonexistent_instrumento(): void
    {
        $proyeccion = Proyeccion::factory()->create();

        $this->deleteJson("/api/proyecciones/{$proyeccion->id}/instrumentos/999")
            ->assertNotFound();
    }
}
