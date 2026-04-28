<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

final class StoreInstitucionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'localidad' => ['required', 'in:Rio Grande,Ushuaia,Tolhuin'],
            'nivel_id' => ['required', 'exists:niveles,id'],
            'cuise' => ['required', 'string', 'max:4'],
            'anexo' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre de la institución es obligatorio.',
            'nombre.string' => 'El nombre debe ser una cadena de texto.',
            'nombre.max' => 'El nombre no puede exceder los 255 caracteres.',
            'localidad.required' => 'La localidad es obligatoria.',
            'localidad.in' => 'La localidad debe ser Rio Grande, Ushuaia o Tolhuin.',
            'nivel_id.required' => 'El nivel es obligatorio.',
            'nivel_id.exists' => 'El nivel seleccionado no existe.',
            'cuise.required' => 'El CUISE es obligatorio.',
            'cuise.string' => 'El CUISE debe ser una cadena de texto.',
            'cuise.max' => 'El CUISE no puede tener más de 4 caracteres.',
            // 'cuise.unique' => 'El CUISE ya está registrado en otra institución.',
            'anexo.string' => 'El anexo debe ser una cadena de texto.',
            'anexo.max' => 'El anexo no puede exceder los 20 caracteres.',
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
