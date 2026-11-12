<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\MotivoProyeccion;
use PHPUnit\Framework\TestCase;

class MotivoProyeccionTest extends TestCase
{
    public function test_enum_cases_exist(): void
    {
        $cases = MotivoProyeccion::cases();

        $this->assertCount(5, $cases);
        $this->assertContainsEquals(MotivoProyeccion::Creacion, $cases);
        $this->assertContainsEquals(MotivoProyeccion::Continuidad, $cases);
        $this->assertContainsEquals(MotivoProyeccion::PlantaFuncional, $cases);
        $this->assertContainsEquals(MotivoProyeccion::Baja, $cases);
        $this->assertContainsEquals(MotivoProyeccion::SinDefinir, $cases);
    }

    public function test_enum_has_correct_string_values(): void
    {
        $this->assertEquals('Creación', MotivoProyeccion::Creacion->value);
        $this->assertEquals('Continuidad', MotivoProyeccion::Continuidad->value);
        $this->assertEquals('Planta funcional', MotivoProyeccion::PlantaFuncional->value);
        $this->assertEquals('Baja', MotivoProyeccion::Baja->value);
        $this->assertEquals('Sin definir', MotivoProyeccion::SinDefinir->value);
    }

    public function test_valores_para_filtro_incluye_todos_los_valores_y_el_alias(): void
    {
        $this->assertSame(
            ['Creación', 'Continuidad', 'Planta funcional', 'Baja', 'Sin definir', 'Creacion'],
            MotivoProyeccion::valoresParaFiltro(),
        );
    }

    public function test_enum_is_backed_string(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, MotivoProyeccion::Creacion);
        $this->assertEquals('string', gettype(MotivoProyeccion::Creacion->value));
    }
}
