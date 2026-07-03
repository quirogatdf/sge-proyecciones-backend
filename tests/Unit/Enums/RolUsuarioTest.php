<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\RolUsuario;
use PHPUnit\Framework\TestCase;

class RolUsuarioTest extends TestCase
{
    public function test_enum_cases_exist(): void
    {
        $cases = RolUsuario::cases();

        $this->assertCount(2, $cases);
        $this->assertContainsEquals(RolUsuario::Admin, $cases);
        $this->assertContainsEquals(RolUsuario::Guest, $cases);
    }

    public function test_enum_has_correct_string_values(): void
    {
        $this->assertEquals('admin', RolUsuario::Admin->value);
        $this->assertEquals('guest', RolUsuario::Guest->value);
    }

    public function test_enum_is_backed_string(): void
    {
        $this->assertInstanceOf(\BackedEnum::class, RolUsuario::Admin);
        $this->assertEquals('string', gettype(RolUsuario::Admin->value));
    }
}
