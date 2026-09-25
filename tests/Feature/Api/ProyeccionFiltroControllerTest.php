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

class ProyeccionFiltroControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    }

    public function test_opciones_devuelve_instituciones_cargos_y_niveles_del_anio(): void
    {
        $nivel = Nivel::factory()->create();
        $institucion = Institucion::factory()->create();
        $cargo = Cargo::factory()->create();

        $p = Proyeccion::factory()->create([
            'id_nivel' => $nivel->id,
            'id_institucion' => $institucion->id,
        ]);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $p->id,
            'anio' => '2026',
            'motivo' => 'Creación',
            'id_cargo' => $cargo->id,
        ]);

        $response = $this->getJson('/api/proyecciones/opciones-filtro?anio=2026&motivo=Creación');

        $response->assertOk()
            ->assertJsonPath('data.instituciones.0.id', $institucion->id)
            ->assertJsonPath('data.cargos.0.id', $cargo->id)
            ->assertJsonPath('data.niveles.0.id', $nivel->id);
    }

    public function test_opciones_acota_por_anio(): void
    {
        $institucion2026 = Institucion::factory()->create();
        $institucion2025 = Institucion::factory()->create();

        $p2026 = Proyeccion::factory()->create(['id_institucion' => $institucion2026->id]);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $p2026->id, 'anio' => '2026']);

        $p2025 = Proyeccion::factory()->create(['id_institucion' => $institucion2025->id]);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $p2025->id, 'anio' => '2025']);

        $response = $this->getJson('/api/proyecciones/opciones-filtro?anio=2026');

        $response->assertOk()
            ->assertJsonCount(1, 'data.instituciones')
            ->assertJsonPath('data.instituciones.0.id', $institucion2026->id);
    }

    public function test_opciones_sin_filtro_devuelve_todos_los_niveles(): void
    {
        Nivel::factory()->count(2)->create();

        $response = $this->getJson('/api/proyecciones/opciones-filtro');

        $response->assertOk()
            ->assertJsonCount(2, 'data.niveles');
    }
}
