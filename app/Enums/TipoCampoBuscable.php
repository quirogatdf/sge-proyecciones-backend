<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Semántica de coincidencia de un campo buscable.
 *
 * - Texto / Enum: contención insensible a mayúsculas (`whereLike`).
 * - Numero: igualdad exacta; un término no numérico produce conjunto vacío.
 * - Fecha: contención sobre la fecha casteada a texto.
 *
 * `Enum` y `Texto` producen el MISMO SQL. La distinción es de documentación y
 * de futuro de UI (un `datalist` de valores posibles), NO de comportamiento.
 */
enum TipoCampoBuscable: string
{
    case Texto = 'texto';
    case Numero = 'numero';
    case Fecha = 'fecha';
    case Enum = 'enum';
}
