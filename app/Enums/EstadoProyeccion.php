<?php declare(strict_types=1);
namespace App\Enums;
enum EstadoProyeccion: string {
    case Autorizado = 'Autorizado';
    case Rechazado = 'Rechazado';
    case Pendiente = 'Pendiente';
}
