<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

/**
 * El IDEAD no se divide en facultades (es una unidad académica paralela a las
 * 10 facultades presenciales): administra directamente sus 12 programas,
 * agrupados por área de estudio (Ciencias Empresariales y Económicas /
 * Ingeniería y Tecnologías / Educación). Este campo permite agrupar la PWA
 * de esa forma en vez de mostrar el IDEAD como una sola "facultad" plana.
 * Nullable y sin efecto en las facultades presenciales (siguen agrupándose
 * por `faculty_id` como siempre).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->string('area_tematica')->nullable()->after('type');
        });

        $groups = [
            'Ciencias Empresariales y Económicas' => ['0803', '0855', '0856'],
            'Ingeniería y Tecnologías' => ['0854', '0853', '0845', '0850', '0838'],
            'Educación' => ['0852', '0851', '0846', '0847'],
        ];
        foreach ($groups as $area => $codes) {
            DB::table('programs')->whereIn('code', $codes)->update(['area_tematica' => $area]);
        }
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('area_tematica');
        });
    }
};
