<?php

namespace Database\Seeders;

use App\Enums\RolUsuario;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@sge.gob.ar'],
            [
                'name' => 'Administrador',
                'username' => 'admin',
                'password' => Hash::make('admin123'),
                'role' => RolUsuario::Admin,
            ]
        );

        User::firstOrCreate(
            ['email' => 'guest@sge.gob.ar'],
            [
                'name' => 'Invitado',
                'username' => 'guest',
                'password' => Hash::make('guest123'),
                'role' => RolUsuario::Guest,
            ]
        );
    }
}
