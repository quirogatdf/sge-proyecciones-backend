<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class StoreResolucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'año' => ['required', 'integer', 'digits:4', 'min:1900', 'max:' . (date('Y') + 10)],
            'observacion' => ['nullable', 'string'],
            'url' => ['nullable', 'string', 'url', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la resolución es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'año.required' => 'El año de la resolución es obligatorio.',
            'año.integer' => 'El año debe ser un número entero.',
            'año.digits' => 'El año debe tener 4 dígitos.',
            'año.min' => 'El año debe ser posterior a 1900.',
            'año.max' => 'El año no puede ser mayor a ' . (date('Y') + 10) . '.',
            'observacion.string' => 'La observación debe ser una cadena de texto.',
            'url.string' => 'La URL debe ser una cadena de texto.',
            'url.url' => 'La URL debe tener un formato válido.',
            'url.max' => 'La URL no puede exceder los 2048 caracteres.',
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
