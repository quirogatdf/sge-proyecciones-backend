<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

final class UpdateInstitucionRequest extends ApiRequest
{
    public function rules(): array
    {
        $institucionId = $this->route('institucion');

        return [
            'nombre' => ['sometimes', 'required', 'string', 'max:255'],
            'localidad' => ['sometimes', 'required', 'in:Rio Grande,Ushuaia,Tolhuin'],
            'nivel_id' => ['sometimes', 'required', 'exists:niveles,id'],
            'cuise' => ['sometimes', 'required', 'string', 'max:4'],
            'anexo' => ['sometimes', 'nullable', 'string', 'max:20'],
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
            'anexo.string' => 'El anexo debe ser una cadena de texto.',
            'anexo.max' => 'El anexo no puede exceder los 20 caracteres.',
        ];
    }
}
