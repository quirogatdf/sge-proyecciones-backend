<?php declare(strict_types=1);
namespace App\Enums;
enum MotivoProyeccion: string {
    case Creacion = 'Creación';
    case Continuidad = 'Continuidad';
    case Baja = 'Baja';
    case SinDefinir = 'Sin definir';
}
