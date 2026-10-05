<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Enums\OrigenCampoBuscable;
use App\Enums\TipoCampoBuscable;
use App\Models\Proyeccion;
use App\Services\CampoBuscable;
use App\Services\RegistroCamposBuscables;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RegistroCamposBuscablesTest extends TestCase
{
    use RefreshDatabase;

    private RegistroCamposBuscables $registro;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registro = new RegistroCamposBuscables;
    }

    // ─────────────────────────── escaparLike (función pura) ───────────────────────────

    public function test_escapar_like_escapa_wildcards_con_backslash(): void
    {
        self::assertSame('100\\%', RegistroCamposBuscables::escaparLike('100%'));
        self::assertSame('a\\_b', RegistroCamposBuscables::escaparLike('a_b'));
        self::assertSame('a\\\\b', RegistroCamposBuscables::escaparLike('a\\b'));
        self::assertSame('texto normal', RegistroCamposBuscables::escaparLike('texto normal'));
    }

    public function test_escapar_like_escapa_el_propio_backslash_primero(): void
    {
        // Si se reemplazara '%' antes que '\', la secuencia quedaría inconsistente.
        self::assertSame('\\\\\\%', RegistroCamposBuscables::escaparLike('\\%'));
    }

    // ─────────────────────────── integridad del registro ───────────────────────────

    public function test_tiene_23_campos(): void
    {
        self::assertCount(23, $this->registro->todos());
    }

    public function test_los_keys_son_unicos(): void
    {
        $keys = array_map(static fn (CampoBuscable $c): string => $c->key, $this->registro->todos());

        self::assertSame($keys, array_unique($keys), 'Hay keys duplicados en el registro.');
    }

    public function test_los_diez_campos_nuevos_estan_presentes(): void
    {
        // Los 10 que el Camino 1 migró a proyeccion_instrumentos y que NO eran buscables.
        $nuevos = [
            'resolucion_ministerial_ext', 'disposicion_sgnij', 'rect_disposoco_sgnij',
            'resolucion_ministerial_rect1', 'resolucion_ministerial_rect2',
            'resolucion_previa_continuidad', 'destino_anterior', 'observaciones',
            'fecha_desde', 'fecha_hasta',
        ];

        foreach ($nuevos as $key) {
            self::assertNotNull($this->registro->buscar($key), "Falta el campo nuevo: {$key}");
        }
    }

    public function test_toda_columna_directa_existe_en_el_esquema(): void
    {
        foreach ($this->registro->todos() as $campo) {
            if ($campo->origen === OrigenCampoBuscable::Relacion) {
                continue; // No son columnas del listado: viven en la tabla del último salto.
            }

            // `columna` viene calificada (`proyecciones.id` / `pi.estado`): hay que
            // separar tabla y nombre de columna antes de consultar el esquema.
            [$tabla, $prefijo, $columna] = $campo->origen === OrigenCampoBuscable::Instrumento
                ? ['proyeccion_instrumentos', 'pi.', $campo->columna]
                : ['proyecciones', 'proyecciones.', $campo->columna];

            self::assertStringStartsWith($prefijo, $campo->columna);
            $columna = substr($campo->columna, strlen($prefijo));

            self::assertTrue(
                Schema::hasColumn($tabla, $columna),
                "La columna {$campo->columna} del campo {$campo->key} no existe en {$tabla}."
            );
        }
    }

    public function test_buscar_devuelve_null_para_un_key_inexistente(): void
    {
        self::assertNull($this->registro->buscar('columna_secreta'));
        self::assertNull($this->registro->buscar(RegistroCamposBuscables::SENTINEL_TODOS));
    }

    // ─────────────────────────── opciones() / endpoint ───────────────────────────

    public function test_opciones_tiene_24_entradas_con_el_sentinel_primero(): void
    {
        $opciones = $this->registro->opciones();

        self::assertCount(24, $opciones);
        self::assertSame(RegistroCamposBuscables::SENTINEL_TODOS, $opciones[0]['key']);
        self::assertSame('Todos los campos', $opciones[0]['label']);
        self::assertNull($opciones[0]['origen']);
        self::assertNull($opciones[0]['tipo']);
    }

    public function test_opciones_conserva_el_orden_del_registro(): void
    {
        $opciones = $this->registro->opciones();

        self::assertSame(
            array_map(static fn (CampoBuscable $c): string => $c->key, $this->registro->todos()),
            array_column(array_slice($opciones, 1), 'key'),
        );
    }

    public function test_opciones_expone_origen_y_tipo_de_cada_campo(): void
    {
        $porKey = array_column($this->registro->opciones(), null, 'key');

        self::assertSame('plaza', $porKey['id']['origen']);
        self::assertSame('numero', $porKey['id']['tipo']);
        self::assertSame('instrumento', $porKey['disposicion_sgnij']['origen']);
        self::assertSame('relacion', $porKey['institucion_nombre']['origen']);
    }

    // ─────────────────────────── aplicar() por tipo ───────────────────────────
    //
    // Acá SÍ se puede inspeccionar `toSql()`: lo que se asserta es la FORMA de la
    // cláusula (`= ?`, `CAST(...)`, `like ?`), NO la compatibilidad del motor. La
    // compatibilidad se prueba en ProyeccionBusquedaTest, que EJECUTA la query.

    public function test_aplicar_numero_genera_igualdad_exacta(): void
    {
        $campo = $this->registro->buscar('id');

        self::assertSame(TipoCampoBuscable::Numero, $campo->tipo);

        $query = Proyeccion::query();
        $this->registro->aplicar($query, $campo, '123', '2026');

        self::assertStringContainsString('= ?', $query->toSql());
        self::assertStringNotContainsString('like', strtolower($query->toSql()));
        self::assertSame([123], $query->getBindings());
    }

    public function test_aplicar_numero_con_termino_no_numerico_da_conjunto_vacio(): void
    {
        $campo = $this->registro->buscar('id');

        $query = Proyeccion::query();
        $this->registro->aplicar($query, $campo, 'abc', '2026');

        self::assertStringContainsString('0 = 1', $query->toSql());
        self::assertSame([], $query->getBindings());
    }

    public function test_aplicar_fecha_emite_cast_explicito(): void
    {
        $campo = $this->registro->buscar('fecha_desde');

        self::assertSame(TipoCampoBuscable::Fecha, $campo->tipo);

        $query = Proyeccion::query();
        $this->registro->aplicar($query, $campo, '2026-01', '2026');

        // El CAST explícito es obligatorio: en PostgreSQL `whereLike` ya castea a ::text,
        // en SQLite NO. Sin esto el camino se rompe justo donde corren los tests.
        self::assertStringContainsString('CAST(pi.fecha_desde AS TEXT)', $query->toSql());
        self::assertSame(['%2026-01%'], $query->getBindings());
    }

    public function test_aplicar_texto_envuelve_el_patron_y_lo_escapa(): void
    {
        $campo = $this->registro->buscar('n_expediente');

        $query = Proyeccion::query();
        $this->registro->aplicar($query, $campo, 'SM_2025', '2026');

        self::assertStringContainsString('like', strtolower($query->toSql()));
        self::assertSame(['%SM\\_2025%'], $query->getBindings());
    }

    public function test_aplicar_relacion_genera_exists_con_anio_acotado(): void
    {
        $cargo = $this->registro->buscar('cargo_nombre');
        $institucion = $this->registro->buscar('institucion_nombre');

        self::assertSame(['instrumentos', 'cargo'], $cargo->relacion);
        self::assertTrue($cargo->anioScope, 'cargo cuelga de instrumentos: debe acotarse al año.');
        self::assertSame(['institucion'], $institucion->relacion);
        self::assertFalse($institucion->anioScope, 'institucion es atributo de la plaza: sin acotar.');

        $query = Proyeccion::query();
        $this->registro->aplicar($query, $cargo, 'Juez', '2026');

        $sql = $query->toSql();
        // EXISTS, nunca JOIN: un JOIN multiplicaría filas y rompería paginate()->total().
        self::assertStringContainsString('exists', strtolower($sql));
        self::assertStringNotContainsString('inner join', strtolower($sql));
        self::assertContains('2026', $query->getBindings());
    }
}
