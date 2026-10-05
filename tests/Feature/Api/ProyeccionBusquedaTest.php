<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Búsqueda avanzada por campo (`search_field`) del listado de proyecciones.
 *
 * ⛔ tests CORRIEN en SQLite `:memory:` (`phpunit.xml`) y PRODUCCIÓN es PostgreSQL.
 * Por eso toda query se EJECUTA con `assertOk()`: en SQLite `ILIKE` COMPILA bien en
 * `toSql()` y sólo revienta al ejecutar (`SQLSTATE[HY000]: near "ILIKE": syntax error`).
 * Un test que assertara `assertStringContainsString('ILIKE', $q->toSql())` daría verde
 * en dev y explotaría en CI — justo al revés de lo que pretende.
 */
class ProyeccionBusquedaTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->admin()->create();
        $this->actingAs($this->adminUser);
    }

    // ─────────────────────────── endpoint de descubrimiento ───────────────────────────

    public function test_campos_buscables_requiere_autenticacion(): void
    {
        auth()->logout();

        $this->getJson('/api/proyecciones/campos-buscables')->assertUnauthorized();
    }

    /**
     * Regresión de ruteo: si alguien mueve la ruta bajo el `apiResource('proyecciones')`,
     * `campos-buscables` se traga como `{proyeccion}` y este test falla.
     */
    public function test_campos_buscables_devuelve_24_entradas_con_el_sentinel_primero(): void
    {
        $response = $this->getJson('/api/proyecciones/campos-buscables');

        $response->assertOk()
            ->assertJsonCount(24, 'data')
            ->assertJsonPath('data.0.key', '__all__')
            ->assertJsonPath('data.0.label', 'Todos los campos')
            ->assertJsonPath('data.0.origen', null)
            ->assertJsonPath('data.0.tipo', null)
            ->assertJsonPath('data.1.key', 'id');
    }

    // ─────────────────────────── portabilidad EJECUTADA ───────────────────────────

    /**
     * El test más importante del cambio. `assertOk()` sólo puede ser 200 si la query
     * se EJECUTÓ de verdad; eso es lo que prueba la portabilidad a SQLite.
     */
    public function test_search_se_ejecuta_realmente_en_sqlite(): void
    {
        $this->crearProyeccion('Autorizado', 'SM-2026-001');

        $this->getJson('/api/proyecciones?anio=2026&search=Autorizado')->assertOk()
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/proyecciones?anio=2026&search=Autorizado&search_field=__all__')->assertOk();
        $this->getJson('/api/proyecciones?anio=2026&search=Autorizado&search_field=estado')->assertOk();
    }

    // ─────────────────────────── equivalencia de __all__ ───────────────────────────

    public function test_search_sin_search_field_equivale_a_all(): void
    {
        $this->crearProyeccion('Autorizado', 'SM-2026-001');

        $sinCampo = $this->getJson('/api/proyecciones?anio=2026&search=Autorizado');
        $conSentinel = $this->getJson('/api/proyecciones?anio=2026&search=Autorizado&search_field=__all__');

        $sinCampo->assertOk();
        $conSentinel->assertOk();

        self::assertSame($sinCampo->json('data'), $conSentinel->json('data'));
    }

    public function test_search_field_como_array_no_revienta(): void
    {
        $this->crearProyeccion('Autorizado', 'SM-2026-001');

        // `?search_field[]=x` llega como array: un Request::string() naïve reventaría.
        $this->getJson('/api/proyecciones?anio=2026&search=Autorizado&search_field[]=x')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    public function test_search_field_con_espacios_alrededor_se_normaliza(): void
    {
        $this->crearProyeccion('Autorizado', 'SM-2026-001');

        $this->getJson('/api/proyecciones?anio=2026&search=Autorizado&search_field=%20estado%20')
            ->assertOk()
            ->assertJsonCount(1, 'data');
    }

    // ─────────────────────────── degradación segura ───────────────────────────

    public function test_search_field_desconocido_degrada_a_all_con_warning(): void
    {
        Log::spy();

        $this->crearProyeccion('Autorizado', 'SM-2026-001');

        $degradado = $this->getJson('/api/proyecciones?anio=2026&search=Autorizado&search_field=columna_secreta');
        $referencia = $this->getJson('/api/proyecciones?anio=2026&search=Autorizado');

        // No 422: el CrudTable no tiene estado de error, un 422 se ve como tabla vacía.
        $degradado->assertOk();
        self::assertSame($referencia->json('data'), $degradado->json('data'));

        Log::shouldHaveReceived('warning')
            ->withArgs(fn (string $msg, array $ctx = []): bool => str_contains($msg, 'search_field')
                && ($ctx['search_field'] ?? null) === 'columna_secreta');
    }

    public function test_search_field_con_inyeccion_sql_no_rompe_nada(): void
    {
        $this->crearProyeccion('Autorizado', 'SM-2026-001');

        $this->getJson('/api/proyecciones?anio=2026&search=Autorizado&search_field='.urlencode('estado; DROP TABLE pi'))
            ->assertOk()
            ->assertJsonCount(1, 'data'); // Degradado a __all__: encuentra la fila.

        // La tabla sigue existiendo.
        $this->getJson('/api/proyecciones?anio=2026')->assertOk();
    }

    // ─────────────────────────── tipo=numero ───────────────────────────

    public function test_search_por_id_es_exacto_y_no_contiene(): void
    {
        // Tres proyecciones: sólo una tiene id numérico "contenido" en otro campo.
        $objetivo = $this->crearProyeccion('Autorizado', 'SM-777-777');
        $otraId = $this->crearProyeccion('Rechazado', 'OTRA-1');
        $tercera = $this->crearProyeccion('Pendiente', 'OTRA-2');

        // El término numérico NO debe matchear por contención en ningún otro campo.
        $this->getJson("/api/proyecciones?anio=2026&search={$objetivo->id}&search_field=id")
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $objetivo->id);

        unset($otraId, $tercera);
    }

    public function test_search_por_id_con_termino_no_numerico_da_total_cero(): void
    {
        $this->crearProyeccion('Autorizado', 'SM-2026-001');

        // No "ignorar el filtro": ignorar devolvería TODAS las filas ante un error de
        // tipeo, peor que una lista vacía porque el usuario cree que filtró.
        $this->getJson('/api/proyecciones?anio=2026&search=abc&search_field=id')
            ->assertOk()
            ->assertJsonPath('meta.total', 0);
    }

    // ─────────────────────────── los 10 campos nuevos ───────────────────────────

    /**
     * @return array<string, array{0: string, 1: string, 2: string}> [campo, valor en DB, término buscado]
     */
    public static function camposNuevosProvider(): array
    {
        return [
            'resolucion_ministerial_ext' => ['resolucion_ministerial_ext', 'EXT-999', 'EXT-999'],
            'disposicion_sgnij' => ['disposicion_sgnij', 'DISP-999', 'DISP-999'],
            'rect_disposoco_sgnij' => ['rect_disposoco_sgnij', 'RECT-999', 'RECT-999'],
            'resolucion_ministerial_rect1' => ['resolucion_ministerial_rect1', 'RECT1-999', 'RECT1-999'],
            'resolucion_ministerial_rect2' => ['resolucion_ministerial_rect2', 'RECT2-999', 'RECT2-999'],
            'resolucion_previa_continuidad' => ['resolucion_previa_continuidad', 'PREVIA-999', 'PREVIA-999'],
            'destino_anterior' => ['destino_anterior', 'ANTERIOR-999', 'ANTERIOR-999'],
            'observaciones' => ['observaciones', 'OBS-999', 'OBS-999'],
            // Las fechas se guardan como date completas; se busca un fragmento.
            'fecha_desde' => ['fecha_desde', '2026-03-15', '2026-03'],
            'fecha_hasta' => ['fecha_hasta', '2026-11-20', '2026-11'],
        ];
    }

    #[DataProvider('camposNuevosProvider')]
    public function test_los_diez_campos_nuevos_son_buscables(string $campo, string $valor, string $termino): void
    {
        $this->crearProyeccion('Autorizado', 'SM-2026-001', extra: [$campo => $valor]);

        $conCampo = $this->getJson("/api/proyecciones?anio=2026&search={$termino}&search_field={$campo}");
        $sinCampo = $this->getJson("/api/proyecciones?anio=2026&search={$termino}");

        $conCampo->assertOk()->assertJsonPath('meta.total', 1);

        // El gap que motiva la feature: `__all__` NO los cubre.
        self::assertSame(0, $sinCampo->json('meta.total'), "`{$campo}` no debería matchear por __all__.");
    }

    // ─────────────────────────── campos de relación ───────────────────────────

    public function test_busqueda_por_institucion_usa_existencia(): void
    {
        $p = $this->crearProyeccion('Autorizado', 'SM-2026-001');
        $p->institucion->update(['nombre' => 'Juzgado Civil Unico', 'localidad' => 'Rio Grande']);

        $this->getJson('/api/proyecciones?anio=2026&search=Civil&search_field=institucion_nombre')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);

        $this->getJson('/api/proyecciones?anio=2026&search=Grande&search_field=institucion_localidad')
            ->assertOk()
            ->assertJsonPath('meta.total', 1);
    }

    // ─────────────────────────── escapado de wildcards ───────────────────────────

    public function test_el_guion_bajo_no_actua_como_wildcard(): void
    {
        // Dos expedientes que sólo se distinguen en el carácter donde va el `_`.
        $conGuion = $this->crearProyeccion('Autorizado', 'ABC_123');
        $conLetra = $this->crearProyeccion('Rechazado', 'ABCX123');

        $ids = $this->getJson('/api/proyecciones?anio=2026&search=ABC_123&search_field=n_expediente')
            ->assertOk()
            ->json('data.*.id');

        // ⚠️ NO se asserta que 'ABC_123' entre. En SQLite el `\` escapado es LITERAL y
        // el escapado degrada (ADR-5 / V6), así que ese match depende del motor y la
        // exactitud queda como QA manual en PostgreSQL. Lo que SÍ se asserta es el
        // invariante que vale en ambos motores: el `_` no actúa como wildcard.
        self::assertNotContains($conLetra->id, $ids, 'El `_` se comportó como wildcard.');

        unset($conGuion);
    }

    // ─────────────────────────── helper ───────────────────────────

    /**
     * @param  array<string, mixed>  $extra
     */
    private function crearProyeccion(
        string $estado = 'Autorizado',
        string $nExpediente = 'SM-2026-001',
        array $extra = [],
    ): Proyeccion {
        $proyeccion = Proyeccion::factory()->create();

        ProyeccionInstrumento::factory()->create(
            array_merge([
                'proyeccion_id' => $proyeccion->id,
                'anio' => '2026',
                'estado' => $estado,
                'n_expediente' => $nExpediente,
                'fecha_desde' => '2026-01-01',
                'fecha_hasta' => '2026-12-31',
            ], $extra)
        );

        return $proyeccion->fresh();
    }
}
