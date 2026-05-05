<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;
use App\Enums\EstadoProyeccion;
use App\Enums\MotivoProyeccion;

final class StoreProyeccionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id_nivel' => ['required', 'integer', 'exists:niveles,id'],
            'estado' => ['required', Rule::enum(EstadoProyeccion::class)],
            'n_expediente' => ['nullable', 'string', 'max:50'],
            'motivo' => ['required', Rule::enum(MotivoProyeccion::class)],
            'orden' => ['nullable', 'integer', 'min:1'],
            'horar' => ['nullable', 'integer', 'min:0'],
            'cargos' => ['nullable', 'integer', 'min:0'],
            'id_cargo' => ['required', 'integer', 'exists:cargos,id'],
            'id_funcion' => ['required', 'integer', 'exists:funciones,id'],
            'id_turno' => ['required', 'integer', 'exists:turnos,id'],
            'fecha_desde' => ['required', 'date'],
            'fecha_hasta' => ['nullable', 'date', 'after_or_equal:fecha_desde'],
            'id_institucion' => ['required', 'integer', 'exists:instituciones,id'],
            'resolucion_ministerial' => ['nullable', 'string', 'max:100'],
            'resolucion_ministerial_ext' => ['nullable', 'string', 'max:100'],
            'disposicion_sgnij' => ['nullable', 'string', 'max:100'],
            'rect_disposoco_sgnij' => ['nullable', 'string', 'max:100'],
            'año' => ['nullable', 'string', 'max:4'],
            'id_puesto' => ['nullable', 'string', 'max:100'],
            'resolucion_ministerial_rect1' => ['nullable', 'string', 'max:100'],
            'resolucion_ministerial_rect2' => ['nullable', 'string', 'max:100'],
            'resolucion_previa_continuidad' => ['nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_nivel.required' => 'El nivel es obligatorio.',
            'id_nivel.integer' => 'El nivel debe ser un número entero.',
            'id_nivel.exists' => 'El nivel seleccionado no existe.',
            'estado.required' => 'El estado es obligatorio.',
            'estado.enum' => 'El estado debe ser: Autorizado, Rechazado o Pendiente.',
            'n_expediente.string' => 'El número de expediente debe ser texto.',
            'n_expediente.max' => 'El número de expediente no puede exceder los 50 caracteres.',
            'motivo.required' => 'El motivo es obligatorio.',
            'motivo.enum' => 'El motivo debe ser: Creación, Continuidad, Baja o Sin definir.',
            'orden.integer' => 'El orden debe ser un número entero.',
            'orden.min' => 'El orden debe ser al menos 1.',
            'horar.integer' => 'Las horas deben ser un número entero.',
            'horar.min' => 'Las horas no pueden ser negativas.',
            'cargos.integer' => 'La cantidad de cargos debe ser un número entero.',
            'cargos.min' => 'La cantidad de cargos no puede ser negativa.',
            'id_cargo.required' => 'El cargo es obligatorio.',
            'id_cargo.integer' => 'El cargo debe ser un número entero.',
            'id_cargo.exists' => 'El cargo seleccionado no existe.',
            'id_funcion.required' => 'La función es obligatoria.',
            'id_funcion.integer' => 'La función debe ser un número entero.',
            'id_funcion.exists' => 'La función seleccionada no existe.',
            'id_turno.required' => 'El turno es obligatorio.',
            'id_turno.integer' => 'El turno debe ser un número entero.',
            'id_turno.exists' => 'El turno seleccionado no existe.',
            'fecha_desde.required' => 'La fecha desde es obligatoria.',
            'fecha_desde.date' => 'La fecha desde debe ser una fecha válida.',
            'fecha_hasta.date' => 'La fecha hasta debe ser una fecha válida.',
            'fecha_hasta.after_or_equal' => 'La fecha hasta debe ser igual o posterior a la fecha desde.',
            'id_institucion.required' => 'La institución es obligatoria.',
            'id_institucion.integer' => 'La institución debe ser un número entero.',
            'id_institucion.exists' => 'La institución seleccionada no existe.',
            'resolucion_ministerial.string' => 'La resolución ministerial debe ser texto.',
            'resolucion_ministerial.max' => 'La resolución ministerial no puede exceder los 100 caracteres.',
            'resolucion_ministerial_ext.string' => 'La extensión de resolución ministerial debe ser texto.',
            'resolucion_ministerial_ext.max' => 'La extensión no puede exceder los 100 caracteres.',
            'disposicion_sgnij.string' => 'La disposición SGNIJ debe ser texto.',
            'disposicion_sgnij.max' => 'La disposición SGNIJ no puede exceder los 100 caracteres.',
            'rect_disposoco_sgnij.string' => 'La rectificación debe ser texto.',
            'rect_disposoco_sgnij.max' => 'La rectificación no puede exceder los 100 caracteres.',
            'año.string' => 'El año debe ser texto.',
            'año.max' => 'El año no puede exceder los 4 caracteres.',
            'id_puesto.string' => 'El puesto debe ser texto.',
            'id_puesto.max' => 'El puesto no puede exceder los 100 caracteres.',
            'resolucion_ministerial_rect1.string' => 'La resolución ministerial rectificada 1 debe ser texto.',
            'resolucion_ministerial_rect1.max' => 'La resolución ministerial rectificada 1 no puede exceder los 100 caracteres.',
            'resolucion_ministerial_rect2.string' => 'La resolución ministerial rectificada 2 debe ser texto.',
            'resolucion_ministerial_rect2.max' => 'La resolución ministerial rectificada 2 no puede exceder los 100 caracteres.',
            'resolucion_previa_continuidad.string' => 'La resolución previa de continuidad debe ser texto.',
            'resolucion_previa_continuidad.max' => 'La resolución previa de continuidad no puede exceder los 100 caracteres.',
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Los datos proporcionados no son válidos.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
