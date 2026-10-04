<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estados de propuesta con el vocabulario de la especificación (RF11, CU25/CU26/CU27).
 *
 * Hasta ahora el enum era PENDIENTE/APROBADA/RECHAZADA y la API traducía a Recibida/Viable/Archivada
 * («puente reversible», decisión de Jose en CU26). Esta migración hace real el cambio:
 *   PENDIENTE → RECIBIDA · APROBADA → VIABLE · RECHAZADA → ARCHIVADA
 *
 * Se hace en tres pasos porque un enum no admite cambiar valores en el sitio: primero la columna pasa a
 * texto, luego se convierten las filas y por último vuelve a ser enum con los valores nuevos. Los datos
 * existentes se conservan (probado en MySQL/MariaDB y SQLite). Reversible.
 *
 * Producción: ejecutar solo esta migración con `--path` (las antiguas no se pueden repetir allí).
 */
return new class extends Migration
{
    private const MAP = ['PENDIENTE' => 'RECIBIDA', 'APROBADA' => 'VIABLE', 'RECHAZADA' => 'ARCHIVADA'];

    public function up(): void
    {
        Schema::table('proposals', fn (Blueprint $t) => $t->string('status', 20)->default('PENDIENTE')->change());

        foreach (self::MAP as $old => $new) {
            DB::table('proposals')->where('status', $old)->update(['status' => $new]);
        }

        Schema::table('proposals', fn (Blueprint $t) => $t->enum('status', ['RECIBIDA', 'VIABLE', 'ARCHIVADA'])->default('RECIBIDA')->change());
    }

    public function down(): void
    {
        Schema::table('proposals', fn (Blueprint $t) => $t->string('status', 20)->default('RECIBIDA')->change());

        foreach (array_flip(self::MAP) as $new => $old) {
            DB::table('proposals')->where('status', $new)->update(['status' => $old]);
        }

        Schema::table('proposals', fn (Blueprint $t) => $t->enum('status', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])->default('PENDIENTE')->change());
    }
};
