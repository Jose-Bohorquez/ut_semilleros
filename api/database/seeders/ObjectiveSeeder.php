<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Seedbed;
use App\Models\Objective;

class ObjectiveSeeder extends Seeder
{
    public function run(): void
    {
        $ia    = Seedbed::where('name', 'Semillero IA y Machine Learning')->first();
        $emp   = Seedbed::where('name', 'Semillero de Emprendimiento Social')->first();
        $bio   = Seedbed::where('name', 'Semillero de Biotecnología Clínica')->first();
        $psi   = Seedbed::where('name', 'Semillero de Psicología Comunitaria')->first();
        $edu   = Seedbed::where('name', 'Semillero de Educación y Tecnología')->first();
        $opt   = Seedbed::where('name', 'Semillero de Optimización de Procesos')->first();

        $objectives = [
            ['seedbed_id' => $ia->id,  'content' => 'Desarrollar modelos predictivos aplicados a la educación virtual',                  'status' => 'ACTIVO'],
            ['seedbed_id' => $ia->id,  'content' => 'Publicar al menos dos artículos en revistas indexadas sobre IA aplicada',           'status' => 'ACTIVO'],
            ['seedbed_id' => $emp->id, 'content' => 'Identificar oportunidades de emprendimiento en comunidades vulnerables',            'status' => 'ACTIVO'],
            ['seedbed_id' => $emp->id, 'content' => 'Formular y validar tres modelos de negocio con impacto social',                     'status' => 'ACTIVO'],
            ['seedbed_id' => $bio->id, 'content' => 'Investigar aplicaciones de biotecnología en diagnóstico de enfermedades tropicales', 'status' => 'ACTIVO'],
            ['seedbed_id' => $psi->id, 'content' => 'Diseñar programas de intervención psicosocial para jóvenes en riesgo',              'status' => 'ACTIVO'],
            ['seedbed_id' => $edu->id, 'content' => 'Evaluar el impacto de las TIC en procesos de enseñanza-aprendizaje',               'status' => 'ACTIVO'],
            ['seedbed_id' => $opt->id, 'content' => 'Aplicar herramientas Lean y Six Sigma en pymes de la región',                      'status' => 'ACTIVO'],
        ];

        foreach ($objectives as $obj) {
            Objective::firstOrCreate(
                ['seedbed_id' => $obj['seedbed_id'], 'content' => $obj['content']],
                $obj
            );
        }
    }
}
