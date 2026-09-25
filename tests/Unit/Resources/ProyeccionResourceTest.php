<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\ProyeccionResource;
use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProyeccionResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_resource_transforms_instrumento_fields(): void
    {
        $proyeccion = Proyeccion::factory()->create();

        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'fecha_hasta' => '2026-12-31',
            'orden' => 123,
            'horar' => 10,
            'cargos' => 5,
            'n_expediente' => 'EXP-123',
            'resolucion_ministerial' => 'RES-123',
        ]);

        $proyeccion->load([
            'nivel',
            'institucion',
            'instrumentos' => fn ($q) => $q->with(['cargo', 'funcion', 'turno', 'resolucion']),
        ]);

        $array = (new ProyeccionResource($proyeccion))->toArray(request());

        $this->assertEquals($proyeccion->id, $array['id']);
        $this->assertEquals('Autorizado', $array['estado']->value);
        $this->assertEquals('Creación', $array['motivo']->value);
        $this->assertEquals('2026-01-01', $array['fecha_desde']->format('Y-m-d'));
        $this->assertEquals('2026', $array['anio']);
        $this->assertEquals(123, $array['orden']);
        $this->assertEquals(10, $array['horar']);
        $this->assertEquals(5, $array['cargos']);
        $this->assertEquals('EXP-123', $array['n_expediente']);
        $this->assertEquals('2026-12-31', $array['fecha_hasta']->format('Y-m-d'));
        $this->assertEquals('RES-123', $array['resolucion_ministerial']);
    }

    public function test_resource_usa_el_instrumento_mas_reciente_como_vigente(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2024', 'n_expediente' => 'VIEJO']);
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026', 'n_expediente' => 'NUEVO']);

        $proyeccion->load(['instrumentos']);

        $array = (new ProyeccionResource($proyeccion))->toArray(request());

        $this->assertEquals('2026', $array['anio']);
        $this->assertEquals('NUEVO', $array['n_expediente']);
    }

    public function test_resource_includes_relationships_when_loaded(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create(['proyeccion_id' => $proyeccion->id, 'anio' => '2026']);

        $proyeccion->load([
            'nivel',
            'institucion',
            'instrumentos' => fn ($q) => $q->with(['cargo', 'funcion', 'turno', 'resolucion']),
        ]);

        $array = (new ProyeccionResource($proyeccion))->toArray(request());

        $this->assertArrayHasKey('nivel', $array);
        $this->assertArrayHasKey('institucion', $array);
        $this->assertArrayHasKey('cargo', $array);
        $this->assertArrayHasKey('funcion', $array);
        $this->assertArrayHasKey('turno', $array);
        $this->assertArrayHasKey('resolucion', $array);
    }

    public function test_resource_sin_instrumentos_devuelve_campos_en_null(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        $proyeccion->load(['nivel', 'institucion']);

        $array = (new ProyeccionResource($proyeccion))->toArray(request());

        $this->assertEquals($proyeccion->id, $array['id']);
        $this->assertNull($array['estado']);
        $this->assertNull($array['motivo']);
        $this->assertNull($array['anio']);
        $this->assertEquals([], $array['instrumentos']);
    }
}
