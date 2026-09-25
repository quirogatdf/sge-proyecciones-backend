<?php

declare(strict_types=1);

namespace Tests\Feature\Migration;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProyeccionInstrumentosTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $migrations = [
            '2026_04_27_121651_create_nivels_table.php',
            '2026_04_27_120600_create_cargos_table.php',
            '2026_04_28_000000_create_funciones_table.php',
            '2026_04_27_121652_create_institucions_table.php',
            '2026_04_28_000000_create_turnos_table.php',
            '2026_04_29_000001_create_proyecciones_table.php',
            '2026_06_23_000001_create_resoluciones_table.php',
            '2026_09_24_000001_create_proyeccion_instrumentos_table.php',
        ];

        foreach ($migrations as $migration) {
            $this->artisan('migrate', ['--path' => "database/migrations/{$migration}"]);
        }
    }

    protected function tearDown(): void
    {
        $migrations = array_reverse([
            '2026_04_27_121651_create_nivels_table.php',
            '2026_04_27_120600_create_cargos_table.php',
            '2026_04_28_000000_create_funciones_table.php',
            '2026_04_27_121652_create_institucions_table.php',
            '2026_04_28_000000_create_turnos_table.php',
            '2026_04_29_000001_create_proyecciones_table.php',
            '2026_06_23_000001_create_resoluciones_table.php',
            '2026_09_24_000001_create_proyeccion_instrumentos_table.php',
        ]);

        foreach ($migrations as $migration) {
            $this->artisan('migrate:rollback', ['--path' => "database/migrations/{$migration}"]);
        }

        parent::tearDown();
    }

    public function test_migration_runs_without_errors(): void
    {
        $this->assertTrue(Schema::hasTable('proyeccion_instrumentos'));
    }

    public function test_table_has_correct_columns(): void
    {
        $columns = Schema::getColumnListing('proyeccion_instrumentos');

        $expectedColumns = [
            'id', 'proyeccion_id', 'anio', 'id_resolucion', 'orden', 'resolucion_ministerial',
            'id_cargo', 'id_funcion', 'id_turno', 'horar', 'cargos',
            'destino_anterior', 'destino_nuevo', 'observaciones', 'created_at', 'updated_at',
        ];

        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $columns, "Columna {$column} no encontrada en proyeccion_instrumentos");
        }
    }

    public function test_un_solo_instrumento_por_proyeccion_y_anio(): void
    {
        $proyeccionId = $this->crearProyeccionBasica();
        $this->insertarInstrumento($proyeccionId, '2026');

        $this->expectException(QueryException::class);
        $this->insertarInstrumento($proyeccionId, '2026');
    }

    public function test_eliminar_proyeccion_elimina_su_historial(): void
    {
        $proyeccionId = $this->crearProyeccionBasica();
        $this->insertarInstrumento($proyeccionId, '2026');

        \DB::table('proyecciones')->where('id', $proyeccionId)->delete();

        $this->assertSame(0, \DB::table('proyeccion_instrumentos')->count());
    }

    private function crearProyeccionBasica(): int
    {
        $nivelId = \DB::table('niveles')->insertGetId(['nombre' => 'Nivel', 'sigla' => 'N', 'created_at' => now(), 'updated_at' => now()]);
        $cargoId = \DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Cargo', 'created_at' => now(), 'updated_at' => now()]);
        $funcionId = \DB::table('funciones')->insertGetId(['nombre' => 'Funcion', 'created_at' => now(), 'updated_at' => now()]);
        $turnoId = \DB::table('turnos')->insertGetId(['nombre' => 'Turno', 'sigla' => 'T', 'created_at' => now(), 'updated_at' => now()]);
        $institucionId = \DB::table('instituciones')->insertGetId([
            'localidad' => 'Ushuaia',
            'nivel_id' => $nivelId,
            'cuise' => '1234',
            'nombre' => 'Institucion',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return \DB::table('proyecciones')->insertGetId([
            'id_nivel' => $nivelId,
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'orden' => '45',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => $cargoId,
            'id_funcion' => $funcionId,
            'id_turno' => $turnoId,
            'id_institucion' => $institucionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertarInstrumento(int $proyeccionId, string $anio): void
    {
        \DB::table('proyeccion_instrumentos')->insert([
            'proyeccion_id' => $proyeccionId,
            'anio' => $anio,
            'orden' => 45,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
