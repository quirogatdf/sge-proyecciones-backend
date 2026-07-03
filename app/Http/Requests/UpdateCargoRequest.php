<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdateCargoRequest extends ApiRequest
{
    public function rules(): array
    {
        $cargoId = $this->route('cargo');

        return [
            'codigo' => ['sometimes', 'required', 'string', 'max:4', Rule::unique('cargos', 'codigo')->ignore($cargoId)],
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string', 'max:500'],
            'tipo' => ['nullable', 'string', 'in:H,C'],
        ];
    }

    public function messages(): array
    {
        return [
            'codigo.required' => 'El código del cargo es obligatorio.',
            'codigo.string' => 'El código debe ser una cadena de texto.',
            'codigo.max' => 'El código no puede exceder los 4 caracteres.',
            'codigo.unique' => 'El código ya está en uso.',
            'nombre.required' => 'El nombre del cargo es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'descripcion.string' => 'La descripción debe ser una cadena de texto.',
            'descripcion.max' => 'La descripción no puede exceder los 500 caracteres.',
            'tipo.in' => 'El tipo debe ser H (Honorario) o C (Contratado).',
        ];
    }
}
