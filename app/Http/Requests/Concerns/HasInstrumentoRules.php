<?php

declare(strict_types=1);

namespace App\Http\Requests\Concerns;

use App\Enums\EstadoProyeccion;
use App\Enums\MotivoProyeccion;
use Illuminate\Validation\Rule;

/**
 * Reglas compartidas de los campos del instrumento (todo lo que varía por año).
 *
 * El prefijo permite reusarlas tanto "planas" (endpoints de instrumento) como
 * anidadas bajo `instrumento.` (crear proyección con su primer instrumento).
 */
trait HasInstrumentoRules
{
    /**
     * @return array<string, array<int, mixed>>
     */
    protected function instrumentoFieldRules(string $prefix = ''): array
    {
        return [
            "{$prefix}estado" => ['nullable', Rule::enum(EstadoProyeccion::class)],
            "{$prefix}motivo" => ['nullable', Rule::enum(MotivoProyeccion::class)],
            "{$prefix}n_expediente" => ['nullable', 'string', 'max:50'],
            "{$prefix}orden" => ['nullable', 'integer', 'min:1'],
            "{$prefix}horar" => ['nullable', 'integer', 'min:0'],
            "{$prefix}cargos" => ['nullable', 'integer', 'min:0'],
            "{$prefix}id_cargo" => ['nullable', 'integer', 'exists:cargos,id'],
            "{$prefix}id_funcion" => ['nullable', 'integer', 'exists:funciones,id'],
            "{$prefix}id_turno" => ['nullable', 'integer', 'exists:turnos,id'],
            "{$prefix}fecha_desde" => ['nullable', 'date'],
            "{$prefix}fecha_hasta" => ['nullable', 'date', "after_or_equal:{$prefix}fecha_desde"],
            "{$prefix}resolucion_ministerial" => ['nullable', 'string', 'max:255'],
            "{$prefix}resolucion_ministerial_ext" => ['nullable', 'string', 'max:255'],
            "{$prefix}disposicion_sgnij" => ['nullable', 'string', 'max:255'],
            "{$prefix}rect_disposoco_sgnij" => ['nullable', 'string', 'max:255'],
            "{$prefix}resolucion_ministerial_rect1" => ['nullable', 'string', 'max:255'],
            "{$prefix}resolucion_ministerial_rect2" => ['nullable', 'string', 'max:255'],
            "{$prefix}resolucion_previa_continuidad" => ['nullable', 'string', 'max:255'],
            "{$prefix}destino_anterior" => ['nullable', 'string', 'max:255'],
            "{$prefix}destino_nuevo" => ['nullable', 'string', 'max:255'],
            "{$prefix}id_resolucion" => ['nullable', 'integer', 'exists:resoluciones,id'],
            "{$prefix}observaciones" => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function instrumentoMessages(string $prefix = ''): array
    {
        return [
            "{$prefix}estado.enum" => 'El estado debe ser: Autorizado, Rechazado o Pendiente.',
            "{$prefix}motivo.enum" => 'El motivo debe ser: Creación, Continuidad, Baja o Sin definir.',
            "{$prefix}fecha_desde.date" => 'La fecha desde debe ser una fecha válida.',
            "{$prefix}fecha_hasta.date" => 'La fecha hasta debe ser una fecha válida.',
            "{$prefix}fecha_hasta.after_or_equal" => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            "{$prefix}id_cargo.exists" => 'El cargo seleccionado no existe.',
            "{$prefix}id_funcion.exists" => 'La función seleccionada no existe.',
            "{$prefix}id_turno.exists" => 'El turno seleccionado no existe.',
            "{$prefix}id_resolucion.exists" => 'La resolución seleccionada no existe.',
            "{$prefix}horar.min" => 'Las horas no pueden ser negativas.',
            "{$prefix}cargos.min" => 'La cantidad de cargos no puede ser negativa.',
        ];
    }
}
