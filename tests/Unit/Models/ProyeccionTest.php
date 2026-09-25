<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Institucion;
use App\Models\Nivel;
use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProyeccionTest extends TestCase
{
    use RefreshDatabase;

    public function test_model_has_correct_fillable(): void
    {
        $this->assertSame(
            ['id_nivel', 'id_institucion', 'id_puesto'],
            (new Proyeccion)->getFillable()
        );
    }

    public function test_ya_no_castea_los_campos_movidos_al_instrumento(): void
    {
        $casts = (new Proyeccion)->getCasts();

        foreach (['estado', 'motivo', 'fecha_desde', 'fecha_hasta', 'orden', 'horar', 'cargos'] as $columna) {
            $this->assertArrayNotHasKey($columna, $casts, "El cast '{$columna}' ya no debería existir en Proyeccion.");
        }
    }

    public function test_nivel_relationship_is_belongs_to(): void
    {
        $relation = (new Proyeccion)->nivel();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('id_nivel', $relation->getForeignKeyName());
    }

    public function test_institucion_relationship_is_belongs_to(): void
    {
        $relation = (new Proyeccion)->institucion();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertSame('id_institucion', $relation->getForeignKeyName());
    }

    public function test_instrumentos_relationship_is_has_many(): void
    {
        $proyeccion = Proyeccion::factory()->create();
        ProyeccionInstrumento::factory()->create([
            'proyeccion_id' => $proyeccion->id,
            'anio' => '2026',
        ]);

        $relation = $proyeccion->instrumentos();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertSame('proyeccion_id', $relation->getForeignKeyName());
        $this->assertCount(1, $proyeccion->fresh()->instrumentos);
    }

    public function test_crea_una_plaza_solo_con_la_base(): void
    {
        $proyeccion = Proyeccion::create([
            'id_nivel' => Nivel::factory()->create()->id,
            'id_institucion' => Institucion::factory()->create()->id,
            'id_puesto' => 'Puesto 1',
        ]);

        $this->assertDatabaseHas('proyecciones', [
            'id' => $proyeccion->id,
            'id_puesto' => 'Puesto 1',
        ]);
    }
}
