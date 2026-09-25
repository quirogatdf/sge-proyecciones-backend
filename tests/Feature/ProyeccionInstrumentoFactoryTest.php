<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\ProyeccionInstrumento;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProyeccionInstrumentoFactoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_factory_crea_snapshot_con_relaciones(): void
    {
        $instrumento = ProyeccionInstrumento::factory()->create();

        $this->assertNotNull($instrumento->id);
        $this->assertNotNull($instrumento->anio);
        $this->assertNotNull($instrumento->proyeccion);
        $this->assertSame($instrumento->proyeccion_id, $instrumento->proyeccion->id);
        $this->assertNotNull($instrumento->cargo);
        $this->assertNotNull($instrumento->funcion);
        $this->assertNotNull($instrumento->turno);
    }

    public function test_un_solo_snapshot_por_anio(): void
    {
        $proyeccion = ProyeccionInstrumento::factory()->create()->proyeccion;

        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
        ]);

        $this->expectException(QueryException::class);
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
        ]);
    }
}
