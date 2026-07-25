<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Proposal;

class ProposalSeeder extends Seeder
{
    public function run(): void
    {
        $estudiante = User::where('email', 'estudiante@ut.edu.co')->first();
        $lider      = User::where('email', 'lider@ut.edu.co')->first();

        $proposals = [
            [
                'user_id'     => $lider->id,
                'title'       => 'Plataforma de monitoreo ambiental con IoT',
                'description' => 'Propuesta para desarrollar sensores de bajo costo para medir calidad del aire en zonas rurales de Santander.',
                'status'      => 'APROBADA',
            ],
            [
                'user_id'     => $lider->id,
                'title'       => 'Algoritmo de detección temprana de deserción estudiantil',
                'description' => 'Sistema de ML que analiza patrones académicos para identificar estudiantes en riesgo de abandono.',
                'status'      => 'PENDIENTE',
            ],
            [
                'user_id'     => $estudiante->id,
                'title'       => 'App móvil para gestión de residuos en campus universitario',
                'description' => 'Aplicación que conecta generadores y gestores de residuos sólidos dentro del campus.',
                'status'      => 'PENDIENTE',
            ],
            [
                'user_id'     => $estudiante->id,
                'title'       => 'Estudio de impacto de redes sociales en salud mental de universitarios',
                'description' => 'Investigación cuantitativa sobre la relación entre uso de redes sociales y niveles de ansiedad.',
                'status'      => 'RECHAZADA',
            ],
            [
                'user_id'     => $lider->id,
                'title'       => 'Diseño de materiales didácticos inclusivos para aulas virtuales',
                'description' => 'Proyecto para crear recursos educativos accesibles para estudiantes con discapacidad visual y auditiva.',
                'status'      => 'APROBADA',
            ],
        ];

        foreach ($proposals as $proposal) {
            Proposal::firstOrCreate(
                ['title' => $proposal['title'], 'user_id' => $proposal['user_id']],
                $proposal
            );
        }
    }
}
