<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Cat;

class CatSeeder extends Seeder
{
    public function run(): void
    {
        $cats = [
            [
                'name'    => 'CAT Bucaramanga',
                'code'    => 'CAT-BGA',
                'address' => 'Calle 35 # 28-40',
                'city'    => 'Bucaramanga',
                'phone1'  => '6076340000',
                'status'  => 'ACTIVO',
            ],
            [
                'name'    => 'CAT Bogotá',
                'code'    => 'CAT-BOG',
                'address' => 'Carrera 7 # 32-16',
                'city'    => 'Bogotá',
                'phone1'  => '6013200000',
                'status'  => 'ACTIVO',
            ],
            [
                'name'    => 'CAT Medellín',
                'code'    => 'CAT-MED',
                'address' => 'Avenida El Poblado # 10-5',
                'city'    => 'Medellín',
                'phone1'  => '6044440000',
                'status'  => 'ACTIVO',
            ],
            [
                'name'    => 'CAT Cali',
                'code'    => 'CAT-CAL',
                'address' => 'Calle 5 # 38-25',
                'city'    => 'Cali',
                'phone1'  => '6023920000',
                'status'  => 'ACTIVO',
            ],
            [
                'name'    => 'CAT Barranquilla',
                'code'    => 'CAT-BAQ',
                'address' => 'Carrera 46 # 67-90',
                'city'    => 'Barranquilla',
                'phone1'  => '6053800000',
                'status'  => 'ACTIVO',
            ],
            [
                'name'    => 'CAT Manizales',
                'code'    => 'CAT-MAN',
                'address' => 'Calle 27 # 15-60',
                'city'    => 'Manizales',
                'phone1'  => '6068810000',
                'status'  => 'INACTIVO',
            ],
        ];

        foreach ($cats as $cat) {
            Cat::updateOrCreate(['code' => $cat['code']], $cat);
        }
    }
}
