<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seedbed;
use App\Models\Result;

class ResultSeeder extends Seeder
{
    public function run(): void
    {
        $ia    = Seedbed::where('name', 'Semillero IA y Machine Learning')->first();
        $emp   = Seedbed::where('name', 'Semillero de Emprendimiento Social')->first();
        $bio   = Seedbed::where('name', 'Semillero de Biotecnología Clínica')->first();
        $psi   = Seedbed::where('name', 'Semillero de Psicología Comunitaria')->first();
        $edu   = Seedbed::where('name', 'Semillero de Educación y Tecnología')->first();
        $opt   = Seedbed::where('name', 'Semillero de Optimización de Procesos')->first();

        $results = [
            ['seedbed_id' => $ia->id,  'content' => 'Prototipo de sistema de recomendación académica basado en redes neuronales', 'status' => 'ACTIVO'],
            ['seedbed_id' => $ia->id,  'content' => 'Ponencia presentada en el Congreso Nacional de Ingeniería 2025',             'status' => 'ACTIVO'],
            ['seedbed_id' => $emp->id, 'content' => 'Plan de negocio social aprobado por el Fondo Emprender',                     'status' => 'ACTIVO'],
            ['seedbed_id' => $emp->id, 'content' => 'Empresa júnior creada con 5 empleos directos generados',                    'status' => 'ACTIVO'],
            ['seedbed_id' => $bio->id, 'content' => 'Artículo publicado en revista SciELO Colombia sobre diagnóstico molecular',  'status' => 'ACTIVO'],
            ['seedbed_id' => $psi->id, 'content' => 'Programa de intervención implementado en 3 colegios públicos',               'status' => 'ACTIVO'],
            ['seedbed_id' => $edu->id, 'content' => 'Aplicación móvil educativa con 200 usuarios activos',                       'status' => 'ACTIVO'],
            ['seedbed_id' => $opt->id, 'content' => 'Reducción del 18% en tiempos de producción en empresa piloto',              'status' => 'ACTIVO'],
        ];

        foreach ($results as $result) {
            Result::firstOrCreate(
                ['seedbed_id' => $result['seedbed_id'], 'content' => $result['content']],
                $result
            );
        }
    }
}
