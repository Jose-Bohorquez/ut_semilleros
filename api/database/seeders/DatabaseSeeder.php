<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            // Independientes (sin FK)
            UserSeeder::class,
            FacultySeeder::class,
            CatSeeder::class,
            AreaSeeder::class,
            GroupSeeder::class,
            CoordinatorSeeder::class,

            // Dependen de Faculty
            ProgramSeeder::class,

            // Depende de Program
            SeedbedSeeder::class,

            // Dependen de Seedbed
            ObjectiveSeeder::class,
            ResultSeeder::class,

            // Dependen de User + Seedbed
            RequestSeeder::class,

            // Depende de User
            ProposalSeeder::class,
        ]);
    }
}
