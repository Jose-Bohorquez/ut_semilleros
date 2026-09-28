<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;

class FacultySeeder extends Seeder
{
    public function run(): void
    {
        /* RF02: códigos de ejemplo para desarrollo (los reales los define la universidad) */
        $faculties = [
            ['code' => 'FIT', 'name' => 'Facultad de Ingeniería y Tecnología',              'status' => 'ACTIVO'],
            ['code' => 'FCEA', 'name' => 'Facultad de Ciencias Económicas y Administrativas', 'status' => 'ACTIVO'],
            ['code' => 'FCS', 'name' => 'Facultad de Ciencias de la Salud',                  'status' => 'ACTIVO'],
            ['code' => 'FCSH', 'name' => 'Facultad de Ciencias Sociales y Humanas',           'status' => 'ACTIVO'],
            ['code' => 'IDEAD', 'name' => 'Facultad de Educación a Distancia',                 'status' => 'ACTIVO'],
            ['code' => 'FCB', 'name' => 'Facultad de Ciencias Básicas',                      'status' => 'INACTIVO'],
        ];

        foreach ($faculties as $faculty) {
            Faculty::updateOrCreate(['name' => $faculty['name']], $faculty);
        }
    }
}
