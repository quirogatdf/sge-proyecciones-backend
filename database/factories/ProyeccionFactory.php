<?php

namespace Database\Factories;

use App\Models\Institucion;
use App\Models\Nivel;
use App\Models\Proyeccion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Proyeccion>
 */
class ProyeccionFactory extends Factory
{
    protected $model = Proyeccion::class;

    public function definition(): array
    {
        return [
            'id_nivel' => Nivel::factory(),
            'id_institucion' => Institucion::factory(),
            'id_puesto' => fake()->optional()->numerify('P-####'),
        ];
    }
}
