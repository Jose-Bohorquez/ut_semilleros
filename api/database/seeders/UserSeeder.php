<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Administrador Sistema',
                'email'    => 'admin@ut.edu.co',
                'password' => Hash::make('12345678'),
                'role'     => 'ADMIN_SISTEMA',
                'status'   => 'ACTIVO',
            ],
            [
                'name'     => 'Administrativo Demo',
                'email'    => 'administrativo@ut.edu.co',
                'password' => Hash::make('12345678'),
                'role'     => 'ADMINISTRATIVO',
                'status'   => 'ACTIVO',
            ],
            [
                'name'     => 'Lider Semillero',
                'email'    => 'lider@ut.edu.co',
                'password' => Hash::make('12345678'),
                'role'     => 'LIDER_SEMILLERO',
                'status'   => 'ACTIVO',
            ],
            [
                'name'     => 'Estudiante Demo',
                'email'    => 'estudiante@ut.edu.co',
                'password' => Hash::make('12345678'),
                'role'     => 'ESTUDIANTE',
                'status'   => 'ACTIVO',
            ],
        ];

        foreach ($users as $user) {
            User::updateOrCreate(['email' => $user['email']], $user);
        }

        // 5 estudiantes adicionales con datos realistas
        $extras = [
            ['name' => 'Laura Camila Vargas',    'email' => 'lvargas@ut.edu.co'],
            ['name' => 'Andrés Felipe Moreno',   'email' => 'afmoreno@ut.edu.co'],
            ['name' => 'Sofía Alejandra Torres', 'email' => 'satorres@ut.edu.co'],
            ['name' => 'Diego Armando Castro',   'email' => 'dacastro@ut.edu.co'],
            ['name' => 'Valentina Ríos Suárez',  'email' => 'vrsuarez@ut.edu.co'],
        ];

        foreach ($extras as $extra) {
            User::updateOrCreate(
                ['email' => $extra['email']],
                [
                    'name'     => $extra['name'],
                    'email'    => $extra['email'],
                    'password' => Hash::make('12345678'),
                    'role'     => 'ESTUDIANTE',
                    'status'   => 'ACTIVO',
                ]
            );
        }
    }
}
