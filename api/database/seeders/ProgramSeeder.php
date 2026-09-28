<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Faculty;
use App\Models\Program;

class ProgramSeeder extends Seeder
{
    public function run(): void
    {
        $ing  = Faculty::where('name', 'Facultad de Ingeniería y Tecnología')->first();
        $eco  = Faculty::where('name', 'Facultad de Ciencias Económicas y Administrativas')->first();
        $sal  = Faculty::where('name', 'Facultad de Ciencias de la Salud')->first();
        $soc  = Faculty::where('name', 'Facultad de Ciencias Sociales y Humanas')->first();
        $edu  = Faculty::where('name', 'Facultad de Educación a Distancia')->first();

        /* RF03: códigos de ejemplo para desarrollo */
        $programs = [
            ['code' => 'ISIS', 'type' => 'PREGRADO', 'name' => 'Ingeniería de Sistemas',              'faculty_id' => $ing->id,  'status' => 'ACTIVO'],
            ['code' => 'IIND', 'type' => 'PREGRADO', 'name' => 'Ingeniería Industrial',               'faculty_id' => $ing->id,  'status' => 'ACTIVO'],
            ['code' => 'ADEM', 'type' => 'PREGRADO', 'name' => 'Administración de Empresas',          'faculty_id' => $eco->id,  'status' => 'ACTIVO'],
            ['code' => 'CONP', 'type' => 'PREGRADO', 'name' => 'Contaduría Pública',                  'faculty_id' => $eco->id,  'status' => 'ACTIVO'],
            ['code' => 'MED', 'type' => 'PREGRADO', 'name' => 'Medicina',                            'faculty_id' => $sal->id,  'status' => 'ACTIVO'],
            ['code' => 'ENF', 'type' => 'PREGRADO', 'name' => 'Enfermería',                          'faculty_id' => $sal->id,  'status' => 'ACTIVO'],
            ['code' => 'PSI', 'type' => 'PREGRADO', 'name' => 'Psicología',                          'faculty_id' => $soc->id,  'status' => 'ACTIVO'],
            ['code' => 'TSOC', 'type' => 'PREGRADO', 'name' => 'Trabajo Social',                      'faculty_id' => $soc->id,  'status' => 'ACTIVO'],
            ['code' => 'LEB', 'type' => 'PREGRADO', 'name' => 'Licenciatura en Educación Básica',    'faculty_id' => $edu->id,  'status' => 'ACTIVO'],
            ['code' => 'LMAT', 'type' => 'PREGRADO', 'name' => 'Licenciatura en Matemáticas',         'faculty_id' => $edu->id,  'status' => 'INACTIVO'],
        ];

        foreach ($programs as $program) {
            Program::updateOrCreate(
                ['name' => $program['name'], 'faculty_id' => $program['faculty_id']],
                $program
            );
        }
    }
}
