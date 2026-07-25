<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\Seedbed;

class SeedbedSeeder extends Seeder
{
    public function run(): void
    {
        $sistemas   = Program::where('name', 'Ingeniería de Sistemas')->first();
        $admin      = Program::where('name', 'Administración de Empresas')->first();
        $medicina   = Program::where('name', 'Medicina')->first();
        $psicologia = Program::where('name', 'Psicología')->first();
        $licencia   = Program::where('name', 'Licenciatura en Educación Básica')->first();
        $industrial = Program::where('name', 'Ingeniería Industrial')->first();

        $seedbeds = [
            ['name' => 'Semillero IA y Machine Learning',       'program_id' => $sistemas->id,   'status' => 'ACTIVO'],
            ['name' => 'Semillero de Emprendimiento Social',     'program_id' => $admin->id,      'status' => 'ACTIVO'],
            ['name' => 'Semillero de Biotecnología Clínica',     'program_id' => $medicina->id,   'status' => 'ACTIVO'],
            ['name' => 'Semillero de Psicología Comunitaria',    'program_id' => $psicologia->id, 'status' => 'ACTIVO'],
            ['name' => 'Semillero de Educación y Tecnología',    'program_id' => $licencia->id,   'status' => 'ACTIVO'],
            ['name' => 'Semillero de Optimización de Procesos',  'program_id' => $industrial->id, 'status' => 'ACTIVO'],
        ];

        foreach ($seedbeds as $seedbed) {
            Seedbed::updateOrCreate(
                ['name' => $seedbed['name'], 'program_id' => $seedbed['program_id']],
                $seedbed
            );
        }
    }
}
