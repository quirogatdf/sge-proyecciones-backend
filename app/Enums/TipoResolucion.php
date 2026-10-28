<?php

declare(strict_types=1);

namespace App\Enums;

enum TipoResolucion: string
{
    case Creacion = 'Creación';
    case Continuidad = 'Continuidad';
    case Rectificacion = 'Rectificación';
    case SinDefinir = 'Sin definir';
}
