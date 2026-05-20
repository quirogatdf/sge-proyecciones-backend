<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\EstadoProyeccion;
use PHPUnit\Framework\TestCase;

class EstadoProyeccionTest extends TestCase
{
    public function test_enum_cases_exist(): void
    {
        $cases = EstadoProyeccion::cases();
        
        $this->assertCount(3, $cases);
        $this->assertContainsEquals(EstadoProyeccion::Autorizado, $cases);
        $this->assertContainsEquals(EstadoProyeccion::Rechazado, $cases);
        $this->assertContainsEquals(EstadoProyeccion::Pendiente, $cases);
    }

    public function test_enum_has_correct_string_values(): void
    {
        $this->assertEquals('Autorizado', EstadoProyeccion::Autorizado->value);
        $this->assertEquals('Rechazado', EstadoProyeccion::Rechazado->value);
        $this->assertEquals('Pendiente', EstadoProyeccion::Pendiente->value);
    }

    public function test_enum_is_backed_string(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, EstadoProyeccion::Autorizado);
        $this->assertEquals('string', gettype(EstadoProyeccion::Autorizado->value));
    }
}
