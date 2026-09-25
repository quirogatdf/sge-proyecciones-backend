<?php

namespace Database\Factories;

use App\Models\Turno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Turno>
 */
class TurnoFactory extends Factory
{
    protected $model = Turno::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement([
                'Mañana',
                'Tarde',
                'Noche',
                'Simple',
            ]),
            'sigla' => fake()->lexify('??'),
        ];
    }
}
