<?php

namespace Database\Factories;

use App\Models\Nivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Nivel>
 */
class NivelFactory extends Factory
{
    protected $model = Nivel::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->unique()->randomElement([
                'Primario',
                'Secundario',
                'Terciario',
                'Inicial',
                'Adultos',
            ]),
            'sigla' => fake()->unique()->lexify('???'),
        ];
    }
}