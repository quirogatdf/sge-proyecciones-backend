<?php

declare(strict_types=1);

namespace App\Enums;

enum MotivoProyeccion: string
{
    case Creacion = 'Creación';
    case Continuidad = 'Continuidad';
    case PlantaFuncional = 'Planta funcional';
    case Baja = 'Baja';
    case SinDefinir = 'Sin definir';

    /**
     * Valores aceptados por los filtros y el export.
     *
     * Incluye el alias sin tilde "Creacion" que envía el frontend por
     * compatibilidad.
     *
     * @return list<string>
     */
    public static function valoresParaFiltro(): array
    {
        return [
            ...array_map(static fn (self $motivo): string => $motivo->value, self::cases()),
            'Creacion',
        ];
    }
}
