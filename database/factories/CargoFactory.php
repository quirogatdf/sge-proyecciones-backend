<?php

namespace Database\Factories;

use App\Models\Cargo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Cargo>
 */
class CargoFactory extends Factory
{
    protected $model = Cargo::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->numerify('####'),
            'nombre' => fake()->unique()->randomElement([
                'Director',
                'Subdirector',
                'Secretario',
                'Tesorero',
                'Vocal',
                'Coordinador',
                'Asesor',
                'Auxiliar',
            ]),
            'descripcion' => fake()->optional()->sentence(),
        ];
    }
}