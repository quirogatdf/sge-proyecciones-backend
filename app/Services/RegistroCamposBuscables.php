<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrigenCampoBuscable;
use App\Enums\TipoCampoBuscable;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;

/**
 * Registro declarativo de los campos buscables del listado de proyecciones.
 *
 * ÚNICA fuente de verdad. De él se derivan DOS artefactos:
 *   (a) las cláusulas WHERE del listado (`aplicar()`)
 *   (b) el payload de `GET /api/proyecciones/campos-buscables` (`opciones()`),
 *       que es lo único que alimenta el picker "Buscar en ▾" del frontend.
 *
 * El frontend manda un `key` público, NUNCA un nombre de columna.
 */
final class RegistroCamposBuscables
{
    /** Sentinel que representa "todos los campos" (comportamiento heredado). */
    public const SENTINEL_TODOS = '__all__';

    /** @var list<CampoBuscable>|null */
    private ?array $todos = null;

    /**
     * Los 23 campos buscables, en orden de presentación.
     *
     * @return list<CampoBuscable>
     */
    public function todos(): array
    {
        if ($this->todos !== null) {
            return $this->todos;
        }

        $instrumento = static fn (string $key, string $label, TipoCampoBuscable $tipo): CampoBuscable => CampoBuscable::columna($key, $label, OrigenCampoBuscable::Instrumento, $tipo, "pi.{$key}");

        return $this->todos = [
            // --- Plaza (tabla `proyecciones`) ---
            CampoBuscable::columna('id', 'ID de proyección', OrigenCampoBuscable::Plaza, TipoCampoBuscable::Numero, 'proyecciones.id'),
            CampoBuscable::columna('id_puesto', 'ID de puesto', OrigenCampoBuscable::Plaza, TipoCampoBuscable::Texto, 'proyecciones.id_puesto'),

            // --- Instrumento (tabla `proyeccion_instrumentos as pi`) ---
            $instrumento('estado', 'Estado', TipoCampoBuscable::Enum),
            $instrumento('motivo', 'Motivo', TipoCampoBuscable::Enum),
            $instrumento('anio', 'Año', TipoCampoBuscable::Texto),
            $instrumento('n_expediente', 'N° de expediente', TipoCampoBuscable::Texto),
            $instrumento('resolucion_ministerial', 'Resolución ministerial', TipoCampoBuscable::Texto),
            // Los 10 que hasta ahora NO eran buscables (migrados en el Camino 1):
            $instrumento('resolucion_ministerial_ext', 'Resolución ministerial (ext.)', TipoCampoBuscable::Texto),
            $instrumento('disposicion_sgnij', 'Disposición SGNIJ', TipoCampoBuscable::Texto),
            $instrumento('rect_disposoco_sgnij', 'Rectificación disposición OCO SGNIJ', TipoCampoBuscable::Texto),
            $instrumento('resolucion_ministerial_rect1', 'Resolución ministerial (rect. 1)', TipoCampoBuscable::Texto),
            $instrumento('resolucion_ministerial_rect2', 'Resolución ministerial (rect. 2)', TipoCampoBuscable::Texto),
            $instrumento('resolucion_previa_continuidad', 'Resolución previa de continuidad', TipoCampoBuscable::Texto),
            $instrumento('destino_anterior', 'Destino anterior', TipoCampoBuscable::Texto),
            $instrumento('destino_nuevo', 'Destino nuevo', TipoCampoBuscable::Texto),
            $instrumento('observaciones', 'Observaciones', TipoCampoBuscable::Texto),
            $instrumento('fecha_desde', 'Fecha desde', TipoCampoBuscable::Fecha),
            $instrumento('fecha_hasta', 'Fecha hasta', TipoCampoBuscable::Fecha),

            // --- Relaciones (EXISTS, nunca JOIN) ---
            CampoBuscable::viaRelacion('institucion_nombre', 'Institución', TipoCampoBuscable::Texto, 'nombre', ['institucion']),
            CampoBuscable::viaRelacion('institucion_localidad', 'Localidad', TipoCampoBuscable::Texto, 'localidad', ['institucion']),
            CampoBuscable::viaRelacion('cargo_nombre', 'Cargo', TipoCampoBuscable::Texto, 'nombre', ['instrumentos', 'cargo'], true),
            CampoBuscable::viaRelacion('cargo_codigo', 'Código de cargo', TipoCampoBuscable::Texto, 'codigo', ['instrumentos', 'cargo'], true),
            CampoBuscable::viaRelacion('resolucion_nombre', 'Resolución (nombre)', TipoCampoBuscable::Texto, 'nombre', ['instrumentos', 'resolucion'], true),
        ];
    }

    /**
     * Resuelve un `key` público contra el registro. `null` si no existe.
     *
     * Un miss NO es un error: el llamador degrada a `__all__`. La seguridad no
     * depende de esta política — el key se resuelve contra un array fijo y no hay
     * interpolación posible en ningún punto.
     */
    public function buscar(string $key): ?CampoBuscable
    {
        foreach ($this->todos() as $campo) {
            if ($campo->key === $key) {
                return $campo;
            }
        }

        return null;
    }

    /**
     * Opciones para el picker "Buscar en ▾": el sentinel primero, luego los campos.
     *
     * El sentinel NO es un `CampoBuscable` porque un campo siempre tiene origen y
     * tipo (no-nullables) y un sentinel no los tiene.
     *
     * @return list<array{key: string, label: string, origen: ?string, tipo: ?string}>
     */
    public function opciones(): array
    {
        return [
            ['key' => self::SENTINEL_TODOS, 'label' => 'Todos los campos', 'origen' => null, 'tipo' => null],
            ...array_map(
                static fn (CampoBuscable $c): array => [
                    'key' => $c->key,
                    'label' => $c->label,
                    'origen' => $c->origen->value,
                    'tipo' => $c->tipo->value,
                ],
                $this->todos(),
            ),
        ];
    }

    /**
     * Punto ÚNICO de concatenación de columnas.
     *
     * Vive en el registro y no en el controller: un controller que concatenara
     * columnas volvería a abrir el agujero que el registro cierra.
     *
     * @param  string  $termino  Término crudo del usuario (aún sin escapar).
     */
    public function aplicar(Builder $query, CampoBuscable $campo, string $termino, string $anio): void
    {
        // Dentro del EXISTS el scope ya es la tabla del último salto → columna sin calificar.
        $columna = $campo->origen === OrigenCampoBuscable::Relacion
            ? $campo->columna
            // En PostgreSQL `whereLike` ya castea a ::text; en SQLite NO. Emitimos
            // el CAST explícito para que el camino no se rompa justo donde corren los tests.
            : ($campo->tipo === TipoCampoBuscable::Fecha
                ? DB::raw("CAST({$campo->columna} AS TEXT)")
                : $campo->columna);

        if ($campo->tipo === TipoCampoBuscable::Numero) {
            // Igualdad EXACTA. Es lo que arregla el problema de producto del `is_numeric`
            // heredado sin tocar `__all__`: buscar 123 con search_field=id devuelve la 123.
            // Un término no numérico da conjunto vacío, no "ignorar el filtro": ignorar
            // devolvería TODAS las filas ante un error de tipeo, peor que una lista vacía.
            if (is_numeric($termino)) {
                $query->where($columna, (int) $termino);
            } else {
                $query->whereRaw('0 = 1');
            }

            return;
        }

        $patron = self::escaparLike($termino);

        if ($campo->relacion === []) {
            $query->whereLike($columna, "%{$patron}%", caseSensitive: false);

            return;
        }

        $this->whereHasCadena($query, $campo->relacion, $campo->anioScope, $columna, $patron, $anio);
    }

    /**
     * ÚNICO punto de escape de wildcards. Se aplica ANTES de envolver en `%…%`
     * y en ambos caminos (`__all__` y campo único).
     *
     * Sin esto, un `_` en la caja de búsqueda matchea cualquier carácter y un `%`
     * matchea todo: `SM-2025_01` devuelve de más.
     *
     * Es una corrección de RESULTADOS, no de performance: el seq scan ocurre igual
     * con término escapado o no, porque el wildcard inicial ya lo fuerza.
     *
     * En PostgreSQL el backslash es el escape por defecto, así que no hace falta
     * cláusula `ESCAPE`. En SQLite el `\` es LITERAL y el escapado degrada: no se
     * ramifica por driver a propósito, porque ramificar haría que testeemos la
     * rama de SQLite y la de producción quedara sin cubrir.
     */
    public static function escaparLike(string $termino): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $termino,
        );
    }

    /**
     * Resuelve la cadena de relaciones como EXISTS anidado.
     *
     * EXISTS y no JOIN: un JOIN a instituciones/cargos multiplicaría filas
     * (proyecciones × instrumentos) y rompería `paginate()->total()`. El listado
     * muestra una fila por plaza, no una por combinación.
     *
     * @param  list<string>  $resto
     */
    private function whereHasCadena(
        Builder $query,
        array $resto,
        bool $anioScope,
        string|Expression $columna,
        string $patron,
        string $anio,
    ): void {
        $rel = array_shift($resto);

        $query->whereHas($rel, function (Builder $sub) use ($resto, $anioScope, $columna, $patron, $anio): void {
            if ($anioScope) {
                $sub->where('anio', $anio);
            }

            if ($resto === []) {
                $sub->whereLike($columna, "%{$patron}%", caseSensitive: false);

                return;
            }

            $this->whereHasCadena($sub, $resto, $anioScope, $columna, $patron, $anio);
        });
    }
}
