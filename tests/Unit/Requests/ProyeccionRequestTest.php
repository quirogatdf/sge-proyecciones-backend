<?php

declare(strict_types=1);

namespace Tests\Unit\Requests;

use App\Http\Requests\StoreProyeccionRequest;
use App\Http\Requests\UpdateProyeccionRequest;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class ProyeccionRequestTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Run necessary migrations
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_121651_create_nivels_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_120600_create_cargos_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_28_000000_create_funciones_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_121652_create_institucions_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_28_000000_create_turnos_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_29_000001_create_proyecciones_table.php']);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_29_000000_create_proyecciones_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_28_000000_create_turnos_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_121652_create_institucions_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_28_000000_create_funciones_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_120600_create_cargos_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_121651_create_nivels_table.php']);
        parent::tearDown();
    }

    public function test_store_request_has_correct_rules(): void
    {
        $request = new StoreProyeccionRequest();
        $rules = $request->rules();
        
        $this->assertArrayHasKey('estado', $rules);
        $this->assertArrayHasKey('motivo', $rules);
        $this->assertArrayHasKey('fecha_desde', $rules);
        $this->assertArrayHasKey('id_nivel', $rules);
        $this->assertArrayHasKey('id_institucion', $rules);
        $this->assertArrayHasKey('id_cargo', $rules);
        $this->assertArrayHasKey('id_funcion', $rules);
        $this->assertArrayHasKey('id_turno', $rules);
        
        // Check estado rules
        $this->assertContains('required', $rules['estado']);
        $this->assertContains('in:Autorizado,Rechazado,Pendiente', $rules['estado']);
        
        // Check motivo rules
        $this->assertContains('required', $rules['motivo']);
        $this->assertContains('in:Creación,Continuidad,Baja,Sin definir', $rules['motivo']);
        
        // Check fecha_desde rules
        $this->assertContains('required', $rules['fecha_desde']);
        $this->assertContains('date', $rules['fecha_desde']);
    }

    public function test_store_request_has_spanish_messages(): void
    {
        $request = new StoreProyeccionRequest();
        $messages = $request->messages();
        
        $this->assertArrayHasKey('estado.required', $messages);
        $this->assertArrayHasKey('motivo.required', $messages);
        $this->assertArrayHasKey('fecha_desde.required', $messages);
    }

    public function test_update_request_has_correct_rules(): void
    {
        $request = new UpdateProyeccionRequest();
        $rules = $request->rules();
        
        // Update request should have same rules as store (all fields required for update too)
        $this->assertArrayHasKey('estado', $rules);
        $this->assertContains('required', $rules['estado']);
    }

    public function test_validation_passes_with_valid_data(): void
    {
        $nivelId = \DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]);
        $cargoId = \DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]);
        $funcionId = \DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]);
        $turnoId = \DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'created_at' => now(), 'updated_at' => now()]);
        $institucionId = \DB::table('instituciones')->insertGetId([
            'localidad' => 'Ushuaia',
            'nivel_id' => \DB::table('niveles')->insertGetId(['nombre' => 'Nivel2', 'sigla' => 'N2', 'created_at' => now(), 'updated_at' => now()]),
            'cuise' => '1234',
            'nombre' => 'Institucion',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        $data = [
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_nivel' => $nivelId,
            'id_institucion' => $institucionId,
            'id_cargo' => $cargoId,
            'id_funcion' => $funcionId,
            'id_turno' => $turnoId,
        ];
        
        $request = new StoreProyeccionRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertTrue($validator->passes());
    }

    public function test_validation_fails_with_invalid_estado(): void
    {
        $data = [
            'estado' => 'Invalido',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_nivel' => 1,
            'id_institucion' => 1,
            'id_cargo' => 1,
            'id_funcion' => 1,
            'id_turno' => 1,
        ];
        
        $request = new StoreProyeccionRequest();
        $validator = Validator::make($data, $request->rules(), $request->messages());
        
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('estado', $validator->errors()->toArray());
    }
}
