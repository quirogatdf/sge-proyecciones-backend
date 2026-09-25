<?php

namespace Database\Factories;

use App\Enums\Localidad;
use App\Models\Institucion;
use App\Models\Nivel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Institucion>
 */
class InstitucionFactory extends Factory
{
    protected $model = Institucion::class;

    public function definition(): array
    {
        return [
            'localidad' => fake()->randomElement([
                Localidad::RioGrande,
                Localidad::Ushuaia,
                Localidad::Tolhuin,
            ]),
            'nivel_id' => Nivel::factory(),
            'cuise' => fake()->unique()->lexify('????'),
            'nombre' => fake()->unique()->words(3, true),
        ];
    }
}
