<?php

declare(strict_types=1);

namespace Tests\Unit\Resources;

use App\Http\Resources\ProyeccionResource;
use App\Models\Proyeccion;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProyeccionResourceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Run all necessary migrations
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_121651_create_nivels_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_120600_create_cargos_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_28_000000_create_funciones_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_121652_create_institucions_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_28_000000_create_turnos_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_29_000001_create_proyecciones_table.php']);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_29_000001_create_proyecciones_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_28_000000_create_turnos_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_121652_create_institucions_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_28_000000_create_funciones_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_120600_create_cargos_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_121651_create_nivels_table.php']);
        parent::tearDown();
    }

    public function test_resource_transforms_basic_fields(): void
    {
        $nivelId = DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]);
        $cargoId = DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]);
        $funcionId = DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]);
        $turnoId = DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'created_at' => now(), 'updated_at' => now()]);
        $institucionId = DB::table('instituciones')->insertGetId([
            'localidad' => 'Ushuaia',
            'nivel_id' => DB::table('niveles')->insertGetId(['nombre' => 'Nivel2', 'sigla' => 'N2', 'created_at' => now(), 'updated_at' => now()]),
            'cuise' => '1234',
            'nombre' => 'Institucion',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $proyeccion = Proyeccion::create([
            'id_nivel' => $nivelId,
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => $cargoId,
            'id_funcion' => $funcionId,
            'id_turno' => $turnoId,
            'id_institucion' => $institucionId,
            'orden' => '123',
            'horar' => 10,
            'cargos' => 5,
            'n_expediente' => 'EXP-123',
            'fecha_hasta' => '2026-12-31',
            'resolucion_ministerial' => 'RES-123',
            'resolucion_ministerial_ext' => 'EXT-123',
            'disposicion_sgnij' => 'DISP-123',
            'rect_disposoco_sgnij' => 'RECT-123',
        ]);

        $resource = new ProyeccionResource($proyeccion);
        $array = $resource->toArray(request());

        $this->assertEquals($proyeccion->id, $array['id']);
        $this->assertEquals('Autorizado', $array['estado']->value);
        $this->assertEquals('Creación', $array['motivo']->value);
        $this->assertEquals('2026-01-01', $array['fecha_desde']->format('Y-m-d'));
        $this->assertEquals('123', $array['orden']);
        $this->assertEquals(10, $array['horar']);
        $this->assertEquals(5, $array['cargos']);
        $this->assertEquals('EXP-123', $array['n_expediente']);
        $this->assertEquals('2026-12-31', $array['fecha_hasta']->format('Y-m-d'));
        $this->assertEquals('RES-123', $array['resolucion_ministerial']);
    }

    public function test_resource_includes_relationships_when_loaded(): void
    {
        // Create related records
        $nivelId = DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]);
        $cargoId = DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]);
        $funcionId = DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]);
        $turnoId = DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'created_at' => now(), 'updated_at' => now()]);
        $institucionId = DB::table('instituciones')->insertGetId([
            'localidad' => 'Ushuaia',
            'nivel_id' => DB::table('niveles')->insertGetId(['nombre' => 'Nivel2', 'sigla' => 'N2', 'created_at' => now(), 'updated_at' => now()]),
            'cuise' => '1234',
            'nombre' => 'Institucion',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $proyeccion = Proyeccion::create([
            'id_nivel' => $nivelId,
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => $cargoId,
            'id_funcion' => $funcionId,
            'id_turno' => $turnoId,
            'id_institucion' => $institucionId,
        ]);

        // Load relationships
        $proyeccion->load(['nivel', 'cargo', 'funcion', 'turno', 'institucion']);

        $resource = new ProyeccionResource($proyeccion);
        $array = $resource->toArray(request());

        $this->assertArrayHasKey('nivel', $array);
        $this->assertArrayHasKey('cargo', $array);
        $this->assertArrayHasKey('funcion', $array);
        $this->assertArrayHasKey('turno', $array);
        $this->assertArrayHasKey('institucion', $array);
    }

    public function test_resource_has_basic_fields_without_relationships(): void
    {
        $nivelId = DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]);
        $cargoId = DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]);
        $funcionId = DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]);
        $turnoId = DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'created_at' => now(), 'updated_at' => now()]);
        $institucionId = DB::table('instituciones')->insertGetId([
            'localidad' => 'Ushuaia',
            'nivel_id' => DB::table('niveles')->insertGetId(['nombre' => 'Nivel2', 'sigla' => 'N2', 'created_at' => now(), 'updated_at' => now()]),
            'cuise' => '1234',
            'nombre' => 'Institucion',
            'created_at' => now(),
            'updated_at' => now()
        ]);

        $proyeccion = Proyeccion::create([
            'id_nivel' => $nivelId,
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => $cargoId,
            'id_funcion' => $funcionId,
            'id_turno' => $turnoId,
            'id_institucion' => $institucionId,
        ]);

        $resource = new ProyeccionResource($proyeccion);
        $json = $resource->toArray(request());

        $this->assertEquals($proyeccion->id, $json['id']);
        $this->assertEquals('Autorizado', $json['estado']->value);
        $this->assertEquals('Creación', $json['motivo']->value);
        $this->assertArrayHasKey('fecha_desde', $json);
        $this->assertArrayHasKey('id_nivel', $json);
        $this->assertArrayHasKey('id_institucion', $json);
    }
}
