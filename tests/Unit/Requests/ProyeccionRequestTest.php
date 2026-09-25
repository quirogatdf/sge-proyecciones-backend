<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreProyeccionRequest;
use App\Http\Requests\UpdateProyeccionRequest;
use App\Models\Institucion;
use App\Models\Nivel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;
use Tests\TestCase;

class ProyeccionRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_request_define_plaza_e_instrumento(): void
    {
        $rules = (new StoreProyeccionRequest)->rules();

        // Plaza
        foreach (['id_nivel', 'id_institucion', 'id_puesto'] as $key) {
            $this->assertArrayHasKey($key, $rules, "Falta la regla de plaza '{$key}'.");
        }
        $this->assertContains('required', $rules['id_nivel']);
        $this->assertContains('required', $rules['id_institucion']);
        $this->assertContains('nullable', $rules['id_puesto']);

        // Instrumento anidado
        $this->assertArrayHasKey('instrumento', $rules);
        $this->assertArrayHasKey('instrumento.anio', $rules);
        $this->assertContains('required_with:instrumento', $rules['instrumento.anio']);

        foreach (['estado', 'motivo', 'fecha_desde', 'id_cargo', 'id_funcion', 'id_turno'] as $key) {
            $this->assertArrayHasKey("instrumento.{$key}", $rules, "Falta la regla 'instrumento.{$key}'.");
        }

        $this->assertTrue(
            collect($rules['instrumento.estado'])->contains(fn ($rule) => $rule instanceof Enum)
        );
        $this->assertTrue(
            collect($rules['instrumento.motivo'])->contains(fn ($rule) => $rule instanceof Enum)
        );
    }

    public function test_store_request_tiene_mensajes_en_espanol(): void
    {
        $messages = (new StoreProyeccionRequest)->messages();

        $this->assertArrayHasKey('id_nivel.required', $messages);
        $this->assertArrayHasKey('id_institucion.required', $messages);
        $this->assertArrayHasKey('instrumento.anio.required_with', $messages);
        $this->assertArrayHasKey('instrumento.estado.enum', $messages);
        $this->assertArrayHasKey('instrumento.motivo.enum', $messages);
    }

    public function test_update_request_solo_permite_editar_la_plaza(): void
    {
        $rules = (new UpdateProyeccionRequest)->rules();

        $this->assertSame(['id_nivel', 'id_institucion', 'id_puesto'], array_keys($rules));

        foreach ($rules as $key => $reglas) {
            $this->assertContains('sometimes', $reglas, "La regla '{$key}' debería ser opcional (sometimes).");
        }
    }

    public function test_store_pasa_con_solo_la_plaza(): void
    {
        $data = [
            'id_nivel' => Nivel::factory()->create()->id,
            'id_institucion' => Institucion::factory()->create()->id,
            'id_puesto' => 'Puesto 1',
        ];

        $request = new StoreProyeccionRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_store_pasa_con_plaza_e_instrumento(): void
    {
        $data = [
            'id_nivel' => Nivel::factory()->create()->id,
            'id_institucion' => Institucion::factory()->create()->id,
            'instrumento' => [
                'anio' => '2026',
                'estado' => 'Autorizado',
                'motivo' => 'Creación',
                'fecha_desde' => '2026-01-01',
            ],
        ];

        $request = new StoreProyeccionRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->passes(), $validator->errors()->toJson());
    }

    public function test_store_falla_con_estado_invalido_en_el_instrumento(): void
    {
        $data = [
            'id_nivel' => Nivel::factory()->create()->id,
            'id_institucion' => Institucion::factory()->create()->id,
            'instrumento' => [
                'anio' => '2026',
                'estado' => 'Invalido',
            ],
        ];

        $request = new StoreProyeccionRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('instrumento.estado', $validator->errors()->toArray());
    }

    public function test_store_requiere_anio_cuando_viene_instrumento(): void
    {
        $data = [
            'id_nivel' => Nivel::factory()->create()->id,
            'id_institucion' => Institucion::factory()->create()->id,
            'instrumento' => ['motivo' => 'Continuidad'],
        ];

        $request = new StoreProyeccionRequest;
        $validator = Validator::make($data, $request->rules(), $request->messages());

        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('instrumento.anio', $validator->errors()->toArray());
    }
}
