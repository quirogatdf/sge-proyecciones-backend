<?php

namespace Database\Factories;

use App\Enums\TipoResolucion;
use App\Models\Resolucion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Resolucion>
 */
class ResolucionFactory extends Factory
{
    protected $model = Resolucion::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->sentence(3),
            'año' => fake()->year(),
            'tipo' => fake()->randomElement(TipoResolucion::cases()),
            'observacion' => fake()->optional()->paragraph(),
            'url' => fake()->optional()->url(),
        ];
    }
}
