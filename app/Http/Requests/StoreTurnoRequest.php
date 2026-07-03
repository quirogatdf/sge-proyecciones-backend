<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class StoreTurnoRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'sigla' => ['nullable', 'string', 'max:10'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del turno es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'sigla.string' => 'La sigla debe ser una cadena de texto.',
            'sigla.max' => 'La sigla no puede exceder los 10 caracteres.',
        ];
    }
}
