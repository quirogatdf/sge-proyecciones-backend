<?php

namespace Database\Factories;

use App\Models\Nivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Nivel>
 */
class NivelFactory extends Factory
{
    protected $model = Nivel::class;

    public function definition(): array
    {
        return [
            'nombre' => fake()->randomElement([
                'Primario',
                'Secundario',
                'Terciario',
                'Inicial',
                'Adultos',
            ]),
            'sigla' => fake()->lexify('???'),
        ];
    }
}
