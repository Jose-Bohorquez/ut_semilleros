<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Coordinator;

class CoordinatorSeeder extends Seeder
{
    public function run(): void
    {
        $coordinators = [
            ['name' => 'María Elena García',     'email' => 'mgarcia@ut.edu.co',     'phone' => '3001234567', 'status' => 'ACTIVO'],
            ['name' => 'Carlos Alberto Rodríguez','email' => 'crodriguez@ut.edu.co',  'phone' => '3109876543', 'status' => 'ACTIVO'],
            ['name' => 'Ana Isabel Martínez',    'email' => 'amartinez@ut.edu.co',    'phone' => '3154567890', 'status' => 'ACTIVO'],
            ['name' => 'Luis Fernando Hernández','email' => 'lhernandez@ut.edu.co',   'phone' => '3002345678', 'status' => 'ACTIVO'],
            ['name' => 'Patricia Sofía López',   'email' => 'plopez@ut.edu.co',       'phone' => '3117654321', 'status' => 'ACTIVO'],
            ['name' => 'Jorge Andrés Pérez',     'email' => 'japerez@ut.edu.co',      'phone' => '3166543210', 'status' => 'INACTIVO'],
        ];

        foreach ($coordinators as $coord) {
            Coordinator::updateOrCreate(['email' => $coord['email']], $coord);
        }
    }
}
