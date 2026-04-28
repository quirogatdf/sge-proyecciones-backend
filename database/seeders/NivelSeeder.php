<?php

namespace Database\Seeders;

use App\Models\Nivel;
use Illuminate\Database\Seeder;

class NivelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $niveles = [
            ['nombre' => 'Nivel Inicial', 'sigla' => 'INI'],
            ['nombre' => 'Nivel Primario', 'sigla' => 'PRI'],
            ['nombre' => 'Nivel Secundario', 'sigla' => 'SEC'],
            ['nombre' => 'Nivel Superior', 'sigla' => 'SUP'],
            ['nombre' => 'Modalidad Especial', 'sigla' => 'ESP'],
            ['nombre' => 'Modalidad Jóvenes y Adultos', 'sigla' => 'ADU'],
            ['nombre' => 'Modalidad Experimental', 'sigla' => 'EXP'],
            ['nombre' => 'Educación No Formal', 'sigla' => 'NOF'],
            ['nombre' => 'Modalidad Técnico Profesional', 'sigla' => 'TEC'],
            ['nombre' => 'Modalidad Rural', 'sigla' => 'RUR'],
            ['nombre' => 'Gestión Privada', 'sigla' => 'PRIV'],
        ];

        foreach ($niveles as $nivel) {
            Nivel::updateOrCreate(
                ['nombre' => $nivel['nombre']],
                ['sigla' => $nivel['sigla']]
            );
        }
    }
}
