<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        $faculties = [
            ['name' => 'Facultad de Ingeniería y Tecnología',              'status' => 'ACTIVO'],
            ['name' => 'Facultad de Ciencias Económicas y Administrativas', 'status' => 'ACTIVO'],
            ['name' => 'Facultad de Ciencias de la Salud',                  'status' => 'ACTIVO'],
            ['name' => 'Facultad de Ciencias Sociales y Humanas',           'status' => 'ACTIVO'],
            ['name' => 'Facultad de Educación a Distancia',                 'status' => 'ACTIVO'],
            ['name' => 'Facultad de Ciencias Básicas',                      'status' => 'INACTIVO'],
        ];

        foreach ($faculties as $faculty) {
            Faculty::updateOrCreate(['name' => $faculty['name']], $faculty);
        }
    }
}
