<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Area;

class AreaSeeder extends Seeder
{
    public function run(): void
    {
        $areas = [
            ['name' => 'Investigación y Desarrollo Tecnológico', 'code' => 'A-IDT', 'status' => 'ACTIVO'],
            ['name' => 'Innovación y Emprendimiento',            'code' => 'A-INN', 'status' => 'ACTIVO'],
            ['name' => 'Ciencias Básicas y Aplicadas',           'code' => 'A-CBA', 'status' => 'ACTIVO'],
            ['name' => 'Salud Pública y Comunitaria',            'code' => 'A-SPC', 'status' => 'ACTIVO'],
            ['name' => 'Humanidades y Ciencias Sociales',        'code' => 'A-HCS', 'status' => 'ACTIVO'],
            ['name' => 'Educación y Pedagogía',                  'code' => 'A-EPE', 'status' => 'ACTIVO'],
        ];

        foreach ($areas as $area) {
            Area::updateOrCreate(['code' => $area['code']], $area);
        }
    }
}
