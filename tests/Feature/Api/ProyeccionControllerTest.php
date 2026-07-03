<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Proyeccion;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProyeccionControllerTest extends TestCase
{
    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Run all necessary migrations
        $this->artisan('migrate', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_03_000001_add_username_to_users_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_07_02_142752_create_personal_access_tokens_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_121651_create_nivels_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_120600_create_cargos_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_28_000000_create_funciones_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_27_121652_create_institucions_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_28_000000_create_turnos_table.php']);
        $this->artisan('migrate', ['--path' => 'database/migrations/2026_04_29_000001_create_proyecciones_table.php']);

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    }

    protected function tearDown(): void
    {
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_29_000001_create_proyecciones_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_28_000000_create_turnos_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_121652_create_institucions_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_28_000000_create_funciones_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_120600_create_cargos_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_04_27_121651_create_nivels_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_02_142752_create_personal_access_tokens_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_03_000001_add_username_to_users_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/2026_07_02_142753_add_role_to_users_table.php']);
        $this->artisan('migrate:rollback', ['--path' => 'database/migrations/0001_01_01_000000_create_users_table.php']);
        parent::tearDown();
    }

    protected function createTestData(): array
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

        return [
            'nivel_id' => $nivelId,
            'cargo_id' => $cargoId,
            'funcion_id' => $funcionId,
            'turno_id' => $turnoId,
            'institucion_id' => $institucionId,
        ];
    }

    public function test_index_returns_200_and_returns_all(): void
    {
        $data = $this->createTestData();
         
        // Create 15 proyecciones
        for ($i = 0; $i < 15; $i++) {
            Proyeccion::create([
                'id_nivel' => $data['nivel_id'],
                'estado' => 'Autorizado',
                'motivo' => 'Creación',
                'fecha_desde' => '2026-01-01',
                'id_cargo' => $data['cargo_id'],
                'id_funcion' => $data['funcion_id'],
                'id_turno' => $data['turno_id'],
                'id_institucion' => $data['institucion_id'],
            ]);
        }
 
        $response = $this->getJson('/api/proyecciones');
         
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [['id', 'estado', 'motivo', 'fecha_desde']],
        ]);
        $this->assertCount(15, $response->json()['data']);
    }

    public function test_index_eager_loads_relationships(): void
    {
        $data = $this->createTestData();
        
        Proyeccion::create([
            'id_nivel' => $data['nivel_id'],
            'estado' => 'Autorizado',
            'motivo' => 'Creación',
            'fecha_desde' => '2026-01-01',
            'id_cargo' => $data['cargo_id'],
            'id_funcion' => $data['funcion_id'],
            'id_turno' => $data['turno_id'],
            'id_institucion' => $data['institucion_id'],
        ]);

        $response = $this->getJson('/api/proyecciones?include=nivel,cargo,funcion,turno,institucion');
        
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertArrayHasKey('nivel', $json['data'][0]);
        $this->assertArrayHasKey('cargo', $json['data'][0]);
    }

    public function test_by_nivel_returns_200_and_filters(): void
    {
        $data = $this->createTestData();
        $otherNivelId = DB::table('niveles')->insertGetId(['nombre' => 'Nivel Otro', 'sigla' => 'NO', 'created_at' => now(), 'updated_at' => now()]);
        
        // Create 3 proyecciones for nivel 1
        for ($i = 0; $i < 3; $i++) {
            Proyeccion::create([
                'id_nivel' => $data['nivel_id'],
                'estado' => 'Autorizado',
                'motivo' => 'Creación',
                'fecha_desde' => '2026-01-01',
                'id_cargo' => $data['cargo_id'],
                'id_funcion' => $data['funcion_id'],
                'id_turno' => $data['turno_id'],
                'id_institucion' => $data['institucion_id'],
            ]);
        }
        
        // Create 2 proyecciones for other nivel
        for ($i = 0; $i < 2; $i++) {
            Proyeccion::create([
                'id_nivel' => $otherNivelId,
                'estado' => 'Autorizado',
                'motivo' => 'Creación',
                'fecha_desde' => '2026-01-01',
                'id_cargo' => $data['cargo_id'],
                'id_funcion' => $data['funcion_id'],
                'id_turno' => $data['turno_id'],
                'id_institucion' => $data['institucion_id'],
            ]);
        }

        $response = $this->getJson("/api/proyecciones/nivel/{$data['nivel_id']}");
        
        $response->assertStatus(200);
        $json = $response->json();
        $this->assertCount(3, $json['data']);
    }
}
