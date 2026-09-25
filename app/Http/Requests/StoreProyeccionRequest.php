<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasInstrumentoRules;

final class StoreProyeccionRequest extends ApiRequest
{
    use HasInstrumentoRules;

    /**
     * Crea una proyección (la "plaza": nivel + institución + puesto) y,
     * opcionalmente, su primer instrumento (paso 2 del wizard) de forma atómica.
     *
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Paso 1: la plaza
            'id_nivel' => ['required', 'integer', 'exists:niveles,id'],
            'id_institucion' => ['required', 'integer', 'exists:instituciones,id'],
            'id_puesto' => ['nullable', 'string', 'max:100'],

            // Paso 2: primer instrumento (opcional -> "crear en blanco")
            'instrumento' => ['sometimes', 'array'],
            'instrumento.anio' => ['required_with:instrumento', 'string', 'max:4'],
        ] + $this->instrumentoFieldRules('instrumento.');
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
            'instrumento.array' => 'El instrumento debe ser un objeto.',
            'instrumento.anio.required_with' => 'El año del instrumento es obligatorio.',
            'instrumento.anio.string' => 'El año del instrumento debe ser texto.',
            'instrumento.anio.max' => 'El año del instrumento no puede exceder los 4 caracteres.',
        ] + $this->instrumentoMessages('instrumento.');
    }
}
