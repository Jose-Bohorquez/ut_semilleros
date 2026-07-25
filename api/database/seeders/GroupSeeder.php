<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Group;

class GroupSeeder extends Seeder
{
    public function run(): void
    {
        $groups = [
            ['name' => 'Grupo Alpha — Inteligencia Artificial',  'code' => 'G-ALP', 'status' => 'ACTIVO'],
            ['name' => 'Grupo Beta — Gestión Empresarial',       'code' => 'G-BET', 'status' => 'ACTIVO'],
            ['name' => 'Grupo Gamma — Biotecnología Aplicada',   'code' => 'G-GAM', 'status' => 'ACTIVO'],
            ['name' => 'Grupo Delta — Psicología Comunitaria',   'code' => 'G-DEL', 'status' => 'ACTIVO'],
            ['name' => 'Grupo Epsilon — Educación Virtual',      'code' => 'G-EPS', 'status' => 'ACTIVO'],
            ['name' => 'Grupo Zeta — Ingeniería de Procesos',    'code' => 'G-ZET', 'status' => 'INACTIVO'],
        ];

        foreach ($groups as $group) {
            Group::updateOrCreate(['code' => $group['code']], $group);
        }
    }
}
