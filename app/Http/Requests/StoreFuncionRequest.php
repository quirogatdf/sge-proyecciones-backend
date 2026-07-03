<?php

declare(strict_types=1);

namespace App\Http\Requests;

final class StoreFuncionRequest extends ApiRequest
{
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'sigla' => ['nullable', 'string', 'max:10'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la función es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'sigla.string' => 'La sigla debe ser una cadena de texto.',
            'sigla.max' => 'La sigla no puede exceder los 10 caracteres.',
            'observacion.string' => 'La observación debe ser una cadena de texto.',
            'observacion.max' => 'La observación no puede exceder los 1000 caracteres.',
        ];
    }
}
