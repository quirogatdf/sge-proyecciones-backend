<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasInstrumentoRules;
use Illuminate\Validation\Rule;

final class StoreProyeccionInstrumentoRequest extends ApiRequest
{
    use HasInstrumentoRules;

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'anio' => [
                'required',
                'string',
                'max:4',
                // Un snapshot por proyección y año
                Rule::unique('proyeccion_instrumentos', 'anio')
                    ->where('proyeccion_id', $this->route('proyeccion')?->id),
            ],
        ] + $this->instrumentoFieldRules();
    }

    public function messages(): array
    {
        return [
            'anio.required' => 'El año es obligatorio.',
            'anio.string' => 'El año debe ser texto.',
            'anio.max' => 'El año no puede exceder los 4 caracteres.',
            'anio.unique' => 'Ya existe un instrumento para este año en esta proyección.',
        ] + $this->instrumentoMessages();
    }
}
