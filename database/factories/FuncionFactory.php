<?php

namespace Database\Factories;

use App\Models\Funcion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Funcion>
 */
class FuncionFactory extends Factory
{
    protected $model = Funcion::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement([
                'Docente',
                'Directivo',
                'Preceptor',
                'Administrativo',
                'Apoyo',
            ]),
            'sigla' => fake()->optional()->lexify('???'),
        ];
    }
}
