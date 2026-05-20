<?php

declare(strict_types=1);

namespace Tests\Feature\Migration;

use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProyeccionesTableTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Run base migrations for referenced tables only
        $baseMigrations = [
            '2026_04_27_121651_create_nivels_table.php',
            '2026_04_27_120600_create_cargos_table.php',
            '2026_04_28_000000_create_funciones_table.php',
            '2026_04_27_121652_create_institucions_table.php',
            '2026_04_28_000000_create_turnos_table.php',
        ];
        
        foreach ($baseMigrations as $migration) {
            $this->artisan('migrate', ['--path' => "database/migrations/{$migration}"]);
        }
        
        // Run our proyecciones migration (user's exact filename)
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_29_000001_create_proyecciones_table.php']);
    }

    protected function tearDown(): void
    {
        // Rollback our migration
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_29_000001_create_proyecciones_table.php']);
        
        // Rollback base migrations
        $baseMigrations = [
            '2026_04_28_000000_create_turnos_table.php',
            '2026_04_27_121652_create_institucions_table.php',
            '2026_04_28_000000_create_funciones_table.php',
            '2026_04_27_120600_create_cargos_table.php',
            '2026_04_27_121651_create_nivels_table.php',
        ];
        
        foreach ($baseMigrations as $migration) {
            $this->artisan('migrate:rollback', ['--path' => "database/migrations/{$migration}"]);
        }
        
        parent::tearDown();
    }

    public function test_migration_runs_without_errors(): void
    {
        $this->assertTrue(Schema::hasTable('proyecciones'));
    }

    public function test_table_has_correct_columns(): void
    {
        $columns = Schema::getColumnListing('proyecciones');
        
        $expectedColumns = [
            'id', 'id_nivel', 'estado', 'n_expediente', 'motivo', 'orden', 'horar', 'cargos',
            'id_cargo', 'id_funcion', 'id_turno', 'fecha_desde', 'fecha_hasta', 'id_institucion',
            'resolucion_ministerial', 'resolucion_ministerial_ext', 'disposicion_sgnij', 'rect_disposoco_sgnij',
            'created_at', 'updated_at'
        ];
        
        foreach ($expectedColumns as $column) {
            $this->assertContains($column, $columns, "Column {$column} not found in proyecciones table");
        }
    }

    public function test_foreign_key_id_nivel_works(): void
    {
        // Create related records using DB facade
        $nivelId = \DB::table('niveles')->insertGetId(['nombre' => 'Test Nivel', 'sigla' => 'TN', 'created_at' => now(), 'updated_at' => now()]);
        $cargoId = \DB::table('cargos')->insertGetId(['codigo' => '1234', 'nombre' => 'Test Cargo', 'created_at' => now(), 'updated_at' => now()]);
        $funcionId = \DB::table('funciones')->insertGetId(['nombre' => 'Test Funcion', 'created_at' => now(), 'updated_at' => now()]);
        $turnoId = \DB::table('turnos')->insertGetId(['nombre' => 'Test Turno', 'created_at' => now(), 'updated_at' => now()]);
        $nivelIdForInstitucion = \DB::table('niveles')->insertGetId(['nombre' => 'Nivel Institucion', 'created_at' => now(), 'updated_at' => now()]);
        $institucionId = \DB::table('instituciones')->insertGetId([
            'localidad' => 'Ushuaia',
            'nivel_id' => $nivelIdForInstitucion,
            'cuise' => '1234',
            'nombre' => 'Test Institucion',
            'created_at' => now(),
            'updated_at' => now()
        ]);
        
        // Insert a proyeccion with valid id_nivel using DB facade
        $proyeccionId = \DB::table('proyecciones')->insertGetId([
            'id_nivel' => $nivelId,
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => $cargoId,
            'id_funcion' => $funcionId,
            'id_turno' => $turnoId,
            'id_institucion' => $institucionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        $this->assertNotNull($proyeccionId);
        
        // Try to insert with invalid id_nivel (should fail if FK works)
        $this->expectException(\Illuminate\Database\QueryException::class);
        \DB::table('proyecciones')->insert([
            'id_nivel' => 9999, // Non-existent
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => $cargoId,
            'id_funcion' => $funcionId,
            'id_turno' => $turnoId,
            'id_institucion' => $institucionId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_all_17_fields_present(): void
    {
        $columns = Schema::getColumnListing('proyecciones');
        // Count all fields: id, 17 fields from spec, created_at, updated_at = 20 total
        $this->assertCount(20, $columns);
    }
}
