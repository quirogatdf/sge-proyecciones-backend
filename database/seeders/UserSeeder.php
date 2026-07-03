<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@sge.gob.ar'],
            [
                'name' => 'Administrador',
                'username' => 'admin',
                'password' => 'admin123',
                'role' => RolUsuario::Admin,
            ]
        );

        User::updateOrCreate(
            ['email' => 'guest@sge.gob.ar'],
            [
                'name' => 'Invitado',
                'username' => 'guest',
                'password' => 'guest123',
                'role' => RolUsuario::Guest,
            ]
        );
    }
}
