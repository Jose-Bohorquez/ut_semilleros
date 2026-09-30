<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/* CU13 Ronda B: la spec pide selección múltiple de programas y áreas por
   semillero (§7, CU13 paso 2), no un solo programa/área como hasta ahora. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seedbed_program', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seedbed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['seedbed_id', 'program_id']);
        });

        Schema::create('seedbed_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seedbed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['seedbed_id', 'area_id']);
        });

        /* Migrar los datos actuales (program_id/area_id de cada semillero)
           a las tablas nuevas antes de quitar las columnas. */
        $seedbeds = DB::table('seedbeds')->select('id', 'program_id', 'area_id')->get();
        $now = now();
        foreach ($seedbeds as $seedbed) {
            if ($seedbed->program_id) {
                DB::table('seedbed_program')->insert([
                    'seedbed_id' => $seedbed->id,
                    'program_id' => $seedbed->program_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
            if ($seedbed->area_id) {
                DB::table('seedbed_area')->insert([
                    'seedbed_id' => $seedbed->id,
                    'area_id' => $seedbed->area_id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Schema::table('seedbeds', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropForeign(['area_id']);
            $table->dropColumn(['program_id', 'area_id']);
        });
    }

    public function down(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('code')->constrained()->nullOnDelete();
            $table->foreignId('area_id')->nullable()->after('program_id')->constrained()->nullOnDelete();
        });

        /* Solo se restaura el primer programa/área de cada semillero: el
           esquema anterior no admitía más de uno. */
        $firstPrograms = DB::table('seedbed_program')->orderBy('id')->get()->groupBy('seedbed_id');
        foreach ($firstPrograms as $seedbedId => $rows) {
            DB::table('seedbeds')->where('id', $seedbedId)->update(['program_id' => $rows->first()->program_id]);
        }
        $firstAreas = DB::table('seedbed_area')->orderBy('id')->get()->groupBy('seedbed_id');
        foreach ($firstAreas as $seedbedId => $rows) {
            DB::table('seedbeds')->where('id', $seedbedId)->update(['area_id' => $rows->first()->area_id]);
        }

        Schema::dropIfExists('seedbed_area');
        Schema::dropIfExists('seedbed_program');
    }
};
