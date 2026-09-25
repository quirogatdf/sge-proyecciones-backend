<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\EstadoProyeccion;
use App\Enums\MotivoProyeccion;
use App\Models\ProyeccionInstrumento;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ProyeccionInstrumentoTest extends TestCase
{
    public function test_model_has_correct_fillable(): void
    {
        $esperado = [
            'proyeccion_id',
            'anio',
            'estado',
            'motivo',
            'n_expediente',
            'fecha_desde',
            'fecha_hasta',
            'id_resolucion',
            'orden',
            'resolucion_ministerial',
            'resolucion_ministerial_ext',
            'disposicion_sgnij',
            'rect_disposoco_sgnij',
            'resolucion_ministerial_rect1',
            'resolucion_ministerial_rect2',
            'resolucion_previa_continuidad',
            'id_cargo',
            'id_funcion',
            'id_turno',
            'horar',
            'cargos',
            'destino_anterior',
            'destino_nuevo',
            'observaciones',
        ];

        $this->assertEquals($esperado, (new ProyeccionInstrumento)->getFillable());
    }

    public function test_estado_y_motivo_se_castean_a_enum(): void
    {
        $instrumento = new ProyeccionInstrumento;
        $instrumento->estado = 'Autorizado';
        $instrumento->motivo = 'Continuidad';

        $this->assertSame(EstadoProyeccion::Autorizado, $instrumento->estado);
        $this->assertSame(MotivoProyeccion::Continuidad, $instrumento->motivo);
    }

    public function test_fechas_se_castean_a_date(): void
    {
        $instrumento = new ProyeccionInstrumento;
        $instrumento->fecha_desde = '2027-03-01';
        $instrumento->fecha_hasta = '2027-12-31';

        $this->assertInstanceOf(Carbon::class, $instrumento->fecha_desde);
        $this->assertSame('2027-03-01', $instrumento->fecha_desde->toDateString());
        $this->assertSame('2027-12-31', $instrumento->fecha_hasta->toDateString());
    }

    public function test_campos_numericos_se_castean_a_integer(): void
    {
        $instrumento = new ProyeccionInstrumento;
        $instrumento->orden = '12';
        $instrumento->horar = '20';
        $instrumento->cargos = '5';

        $this->assertSame(12, $instrumento->orden);
        $this->assertSame(20, $instrumento->horar);
        $this->assertSame(5, $instrumento->cargos);
    }

    public function test_relaciones_son_belongs_to(): void
    {
        $instrumento = new ProyeccionInstrumento;

        foreach (['proyeccion', 'resolucion', 'cargo', 'funcion', 'turno'] as $relacion) {
            $this->assertInstanceOf(BelongsTo::class, $instrumento->{$relacion}());
        }
    }
}
