<?php

namespace Database\Factories;

use App\Models\Resolucion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Resolucion>
 */
class ResolucionFactory extends Factory
{
    protected $model = Resolucion::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->sentence(3),
            'año' => fake()->year(),
            'observacion' => fake()->optional()->paragraph(),
            'url' => fake()->optional()->url(),
        ];
    }
}
