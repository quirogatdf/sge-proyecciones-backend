<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class UpdateProyeccionRequest extends ApiRequest
{
    /**
     * Actualiza solo la "plaza" (nivel + institución + puesto).
     * Los datos que varían por año se editan vía el endpoint de instrumentos.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'id_nivel' => ['sometimes', 'required', 'integer', 'exists:niveles,id'],
            'id_institucion' => ['sometimes', 'required', 'integer', 'exists:instituciones,id'],
            'id_puesto' => ['sometimes', 'nullable', 'string', 'max:100'],
        ];
    }

    public function messages(): array
    {
        return [
            'id_nivel.required' => 'El nivel es obligatorio.',
            'id_nivel.exists' => 'El nivel seleccionado no existe.',
            'id_institucion.required' => 'La institución es obligatoria.',
            'id_institucion.exists' => 'La institución seleccionada no existe.',
            'id_puesto.string' => 'El puesto debe ser texto.',
            'id_puesto.max' => 'El puesto no puede exceder los 100 caracteres.',
        ];
    }
}
