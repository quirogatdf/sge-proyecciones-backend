<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Dónde vive la columna de un campo buscable.
 *
 * Determina cómo se aplica el filtro en el listado de proyecciones:
 *  - Instrumento: `proyeccion_instrumentos as pi` (snapshot por año).
 *  - Plaza: `proyecciones` (identidad de la plaza).
 *  - Relacion: no es una columna del listado; se resuelve con `whereHas` (EXISTS).
 */
enum OrigenCampoBuscable: string
{
    case Instrumento = 'instrumento';
    case Plaza = 'plaza';
    case Relacion = 'relacion';
}
