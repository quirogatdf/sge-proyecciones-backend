<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\EstadoProyeccion;
use App\Enums\MotivoProyeccion;
use App\Models\Proyeccion;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;

class ProyeccionTest extends TestCase
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

    public function test_model_has_correct_fillable(): void
    {
        $proyeccion = new Proyeccion();
        $expectedFillable = [
            'id_nivel', 'estado', 'n_expediente', 'motivo', 'orden', 'horar', 'cargos',
            'id_cargo', 'id_funcion', 'id_turno', 'fecha_desde', 'fecha_hasta', 'id_institucion',
            'resolucion_ministerial', 'resolucion_ministerial_ext', 'disposicion_sgnij', 'rect_disposoco_sgnij',
        ];
        
        $this->assertEquals($expectedFillable, $proyeccion->getFillable());
    }

    public function test_estado_casts_to_enum(): void
    {
        $proyeccion = Proyeccion::create([
            'id_nivel' => \DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]),
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => \DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]),
            'id_funcion' => \DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]),
            'id_turno' => \DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'created_at' => now(), 'updated_at' => now()]),
            'id_institucion' => \DB::table('instituciones')->insertGetId([
                'localidad' => 'Ushuaia',
                'nivel_id' => \DB::table('niveles')->insertGetId(['nombre' => 'Nivel2', 'sigla' => 'N2', 'created_at' => now(), 'updated_at' => now()]),
                'cuise' => '1234',
                'nombre' => 'Institucion',
                'created_at' => now(),
                'updated_at' => now()
            ]),
        ]);
        
        $this->assertInstanceOf(EstadoProyeccion::class, $proyeccion->estado);
        $this->assertEquals(EstadoProyeccion::Autorizado, $proyeccion->estado);
    }

    public function test_motivo_casts_to_enum(): void
    {
        $proyeccion = Proyeccion::create([
            'id_nivel' => \DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]),
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => \DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]),
            'id_funcion' => \DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]),
            'id_turno' => \DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'created_at' => now(), 'updated_at' => now()]),
            'id_institucion' => \DB::table('instituciones')->insertGetId([
                'localidad' => 'Ushuaia',
                'nivel_id' => \DB::table('niveles')->insertGetId(['nombre' => 'Nivel2', 'sigla' => 'N2', 'created_at' => now(), 'updated_at' => now()]),
                'cuise' => '1234',
                'nombre' => 'Institucion',
                'created_at' => now(),
                'updated_at' => now()
            ]),
        ]);
        
        $this->assertInstanceOf(MotivoProyeccion::class, $proyeccion->motivo);
        $this->assertEquals(MotivoProyeccion::Creacion, $proyeccion->motivo);
    }

    public function test_fecha_desde_casts_to_date(): void
    {
        $proyeccion = Proyeccion::create([
            'id_nivel' => \DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]),
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => \DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]),
            'id_funcion' => \DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]),
            'id_turno' => \DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'created_at' => now(), 'updated_at' => now()]),
            'id_institucion' => \DB::table('instituciones')->insertGetId([
                'localidad' => 'Ushuaia',
                'nivel_id' => \DB::table('niveles')->insertGetId(['nombre' => 'Nivel2', 'sigla' => 'N2', 'created_at' => now(), 'updated_at' => now()]),
                'cuise' => '1234',
                'nombre' => 'Institucion',
                'created_at' => now(),
                'updated_at' => now()
            ]),
        ]);
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $proyeccion->fecha_desde);
        $this->assertEquals('2026-01-01', $proyeccion->fecha_desde->format('Y-m-d'));
    }

    public function test_nivel_relationship_is_belongs_to(): void
    {
        $proyeccion = new Proyeccion();
        $relation = $proyeccion->nivel();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_nivel', $relation->getForeignKeyName());
    }

    public function test_cargo_relationship_is_belongs_to(): void
    {
        $proyeccion = new Proyeccion();
        $relation = $proyeccion->cargo();
        
        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals('id_cargo', $relation->getForeignKeyName());
    }
}
