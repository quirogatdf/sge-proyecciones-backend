<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Cargo;
use App\Models\Institucion;
use App\Models\Nivel;
use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProyeccionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    }

    public function test_index_returns_plazas_con_instrumento_del_anio(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
        ]);

        $response = $this->getJson('/api/proyecciones?anio=2026');

        $response->assertOk()
            ->assertJsonStructure(['data' => [['id', 'estado', 'motivo', 'fecha_desde', 'anio']]])
            ->assertJsonPath('data.0.id', $proyeccion->id)
            ->assertJsonPath('data.0.estado', 'Autorizado')
            ->assertJsonPath('data.0.motivo', 'Creación')
            ->assertJsonPath('data.0.anio', '2026')
            ->assertJsonPath('meta.anio', '2026');
    }

    public function test_index_default_usa_ultimo_anio_con_datos(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2025']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $response = $this->getJson('/api/proyecciones');

        $response->assertOk()
            ->assertJsonPath('meta.anio', '2026')
            ->assertJsonPath('data.0.anio', '2026');
    }

    public function test_index_solo_incluye_plazas_con_instrumento_de_ese_anio(): void
    {
        $conInstrumento = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $conInstrumento->id, 'anio' => '2026']);

        $sinInstrumento = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $sinInstrumento->id, 'anio' => '2025']);

        $response = $this->getJson('/api/proyecciones?anio=2026');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $conInstrumento->id);
    }

    public function test_index_eager_loads_relationships(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $response = $this->getJson('/api/proyecciones?anio=2026');

        $response->assertOk();
        $json = $response->json();
        $this->assertArrayHasKey('nivel', $json['data'][0]);
        $this->assertArrayHasKey('institucion', $json['data'][0]);
        $this->assertArrayHasKey('cargo', $json['data'][0]);
        $this->assertArrayHasKey('resolucion', $json['data'][0]);
    }

    public function test_index_expone_anios_disponibles_en_meta(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2024']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $response = $this->getJson('/api/proyecciones?anio=2026');

        $response->assertOk()
            ->assertJsonPath('meta.anios_disponibles', ['2026', '2024']);
    }

    public function test_by_nivel_returns_200_and_filters(): void
    {
        $nivelA = Nivel::factory()->create();
        $nivelB = Nivel::factory()->create();

        foreach (range(1, 3) as $i) {
            $p = Proyeccion::factory()->create(['id_nivel' => $nivelA->id]);
            ProyeccionInstrumento::factory()->create(['proyeccion_id' => $p->id, 'anio' => '2026']);
        }

        $pB = Proyeccion::factory()->create(['id_nivel' => $nivelB->id]);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $pB->id, 'anio' => '2026']);

        $response = $this->getJson("/api/proyecciones/nivel/{$nivelA->id}?anio=2026");

        $response->assertOk()
            ->assertJsonCount(3, 'data');
    }

    public function test_show_returns_historial_completo(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2024']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2025']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $response = $this->getJson("/api/proyecciones/{$proyeccion->id}");

        $response->assertOk()
            ->assertJsonCount(3, 'data.instrumentos')
            ->assertJsonPath('data.instrumentos.0.anio', '2026')
            ->assertJsonPath('data.anio', '2026');
    }

    public function test_show_returns_404_for_nonexistent(): void
    {
        $this->getJson('/api/proyecciones/999')->assertNotFound();
    }

    public function test_stats_by_institucion_agrupa_por_institucion(): void
    {
        $institucion = Institucion::factory()->create();

        $p1 = Proyeccion::factory()->create(['id_institucion' => $institucion->id]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p1->id,
            'anio' => '2026',
            'motivo' => 'Creación',
            'horar' => 10,
            'cargos' => null,
        ]);

        $p2 = Proyeccion::factory()->create(['id_institucion' => $institucion->id]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p2->id,
            'anio' => '2026',
            'motivo' => 'Continuidad',
            'horar' => null,
            'cargos' => 5,
        ]);

        $response = $this->getJson('/api/proyecciones/stats/by-institucion?anio=2026');

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.institucion_id', $institucion->id)
            ->assertJsonPath('data.0.creacion_horas_h', 10)
            ->assertJsonPath('data.0.continuidad_no_h', 5);
    }

    public function test_stats_by_institucion_ignora_motivos_fuera_de_creacion_continuidad(): void
    {
        $institucion = Institucion::factory()->create();
        $p = Proyeccion::factory()->create(['id_institucion' => $institucion->id]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p->id,
            'anio' => '2026',
            'motivo' => 'Baja',
            'horar' => 10,
        ]);

        $response = $this->getJson('/api/proyecciones/stats/by-institucion?anio=2026');

        $response->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_store_crea_la_plaza_sin_instrumento(): void
    {
        $nivel = Nivel::factory()->create();
        $institucion = Institucion::factory()->create();

        $response = $this->postJson('/api/proyecciones', [
            'id_nivel' => $nivel->id,
            'id_institucion' => $institucion->id,
            'id_puesto' => 'Puesto X',
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.id_puesto', 'Puesto X')
            ->assertJsonPath('data.anio', null);

        $this->assertDatabaseHas('proyecciones', [
            'id_nivel' => $nivel->id,
            'id_institucion' => $institucion->id,
            'id_puesto' => 'Puesto X',
        ]);
        $this->assertDatabaseCount('proyeccion_instrumentos', 0);
    }

    public function test_store_crea_plaza_y_primer_instrumento_atomicamente(): void
    {
        $nivel = Nivel::factory()->create();
        $institucion = Institucion::factory()->create();

        $response = $this->postJson('/api/proyecciones', [
            'id_nivel' => $nivel->id,
            'id_institucion' => $institucion->id,
            'instrumento' => [
                'anio' => '2026',
                'estado' => 'Autorizado',
                'motivo' => 'Creación',
                'destino_nuevo' => 'Juzgado Civil N° 1',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.anio', '2026')
            ->assertJsonPath('data.estado', 'Autorizado')
            ->assertJsonPath('data.motivo', 'Creación');

        $proyeccionId = $response->json('data.id');
        $this->assertDatabaseHas('proyeccion_instrumentos', [
            'proyeccion_id' => $proyeccionId,
            'anio' => '2026',
            'estado' => 'Autorizado',
        ]);
    }

    public function test_store_valida_la_plaza_requerida(): void
    {
        $this->postJson('/api/proyecciones', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['id_nivel', 'id_institucion']);
    }

    public function test_store_falla_con_estado_invalido_en_el_instrumento(): void
    {
        $this->postJson('/api/proyecciones', [
            'id_nivel' => Nivel::factory()->create()->id,
            'id_institucion' => Institucion::factory()->create()->id,
            'instrumento' => ['anio' => '2026', 'estado' => 'Invalido'],
        ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['instrumento.estado']);
    }

    public function test_update_actualiza_solo_la_plaza(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $nivel = Nivel::factory()->create();

        $response = $this->putJson("/api/proyecciones/{$proyeccion->id}", [
            'id_nivel' => $nivel->id,
            'id_puesto' => 'Puesto Editado',
        ]);

        $response->assertOk()
            ->assertJsonPath('data.id_puesto', 'Puesto Editado');

        $this->assertDatabaseHas('proyecciones', [
            'id' => $proyeccion->id,
            'id_nivel' => $nivel->id,
            'id_puesto' => 'Puesto Editado',
        ]);
    }

    public function test_stats_por_anio_agrupa_cargos_y_horas_por_anio(): void
    {
        $cargoTipoC = Cargo::factory()->create(['tipo' => 'C']);
        $cargoTipoH1 = Cargo::factory()->create(['tipo' => 'H']);
        $cargoTipoH2 = Cargo::factory()->create(['tipo' => 'H']);

        // Cargo tipo C en 2024
        $p1 = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p1->id,
            'anio' => '2024',
            'id_cargo' => $cargoTipoC->id,
        ]);

        // Dos instrumentos tipo C en 2025 (distintas plazas)
        $p2 = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p2->id,
            'anio' => '2025',
            'id_cargo' => $cargoTipoC->id,
        ]);
        $p3 = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p3->id,
            'anio' => '2025',
            'id_cargo' => $cargoTipoC->id,
        ]);

        // Tipo H en 2025 y 2026 (suma de horar)
        $p4 = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p4->id,
            'anio' => '2025',
            'id_cargo' => $cargoTipoH1->id,
            'horar' => 10,
        ]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p4->id,
            'anio' => '2026',
            'id_cargo' => $cargoTipoH2->id,
            'horar' => 20,
        ]);

        $response = $this->getJson('/api/proyecciones/stats/por-anio');

        $response->assertOk()
            ->assertJsonCount(2, 'data.cargos')
            ->assertJsonCount(2, 'data.horas')
            // Cargos: 2024 = 1, 2025 = 2 (orden ascendente por año)
            ->assertJsonPath('data.cargos.0', ['year' => 2024, 'count' => 1])
            ->assertJsonPath('data.cargos.1', ['year' => 2025, 'count' => 2])
            // Horas: 2025 = 10, 2026 = 20
            ->assertJsonPath('data.horas.0', ['year' => 2025, 'totalHoras' => 10])
            ->assertJsonPath('data.horas.1', ['year' => 2026, 'totalHoras' => 20]);
    }

    public function test_stats_por_anio_filtra_por_institucion(): void
    {
        $cargoTipoC = Cargo::factory()->create(['tipo' => 'C']);
        $institucionA = Institucion::factory()->create();
        $institucionB = Institucion::factory()->create();

        $pA = Proyeccion::factory()->create(['id_institucion' => $institucionA->id]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $pA->id,
            'anio' => '2026',
            'id_cargo' => $cargoTipoC->id,
        ]);

        $pB = Proyeccion::factory()->create(['id_institucion' => $institucionB->id]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $pB->id,
            'anio' => '2026',
            'id_cargo' => $cargoTipoC->id,
        ]);

        $response = $this->getJson("/api/proyecciones/stats/por-anio?institucion_id={$institucionA->id}");

        $response->assertOk()
            ->assertJsonCount(1, 'data.cargos')
            ->assertJsonPath('data.cargos.0.count', 1)
            ->assertJsonPath('data.horas', []);
    }
}
