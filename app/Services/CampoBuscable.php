<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\OrigenCampoBuscable;
use App\Enums\TipoCampoBuscable;

/**
 * Campo buscable del listado de proyecciones.
 *
 * Value object readonly. `key` es el identificador PÚBLICO (lo que viaja por HTTP);
 * `columna` es interno y nunca sale del backend.
 */
final readonly class CampoBuscable
{
    /**
     * @param  string  $columna  Columna calificada exactamente como hace falta en el punto
     *                           de aplicación: `proyecciones.id` / `pi.estado` para
     *                           origen Plaza|Instrumento; SIN calificar para Relacion
     *                           (dentro del EXISTS el scope ya es la tabla del último salto).
     * @param  list<string>  $relacion  Cadena de relaciones desde Proyeccion. Vacía cuando
     *                                  origen !== Relacion. Ej: ['institucion'] | ['instrumentos','cargo'].
     * @param  bool  $anioScope  Si la cadena debe acotarse al año en foco. Necesario cuando
     *                           la relación cuelga de `instrumentos` (abarca todos los años);
     *                           sin acotar matchearía el cargo de 2023 desde un listado de 2025.
     */
    public function __construct(
        public string $key,
        public string $label,
        public OrigenCampoBuscable $origen,
        public TipoCampoBuscable $tipo,
        public string $columna,
        public array $relacion = [],
        public bool $anioScope = false,
    ) {}

    /** Campo sobre una columna directa del listado (Plaza o Instrumento). */
    public static function columna(
        string $key,
        string $label,
        OrigenCampoBuscable $origen,
        TipoCampoBuscable $tipo,
        string $columna,
    ): self {
        return new self($key, $label, $origen, $tipo, $columna);
    }

    /**
     * Campo alcanzado a través de relaciones (EXISTS).
     *
     * @param  list<string>  $cadena
     */
    public static function viaRelacion(
        string $key,
        string $label,
        TipoCampoBuscable $tipo,
        string $columna,
        array $cadena,
        bool $anioScope = false,
    ): self {
        return new self(
            $key,
            $label,
            OrigenCampoBuscable::Relacion,
            $tipo,
            $columna,
            $cadena,
            $anioScope,
        );
    }
}
