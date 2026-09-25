<?php

namespace Database\Factories;

use App\Models\Cargo;
use App\Models\Funcion;
use App\Models\Proyeccion;
use App\Models\ProyeccionInstrumento;
use App\Models\Resolucion;
use App\Models\Turno;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ProyeccionInstrumento>
 */
class ProyeccionInstrumentoFactory extends Factory
{
    protected $model = ProyeccionInstrumento::class;

    public function definition(): array
    {
        return [
            'proyeccion_id' => Proyeccion::factory(),
            'anio' => (string) fake()->year(),
            'id_resolucion' => Resolucion::factory(),
            'orden' => fake()->numberBetween(1, 500),
            'resolucion_ministerial' => fake()->optional()->catchPhrase(),
            'id_cargo' => Cargo::factory(),
            'id_funcion' => Funcion::factory(),
            'id_turno' => Turno::factory(),
            'horar' => fake()->optional()->numberBetween(1, 40),
            'cargos' => fake()->optional()->numberBetween(1, 100),
            'destino_anterior' => fake()->optional()->city(),
            'destino_nuevo' => fake()->optional()->city(),
            'observaciones' => fake()->optional()->sentence(),
        ];
    }
}
