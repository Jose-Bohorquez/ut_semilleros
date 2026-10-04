<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Cancelación de la postulación por el propio estudiante (por si se equivocó): nuevo estado CANCELADA en las
 * solicitudes de vinculación. La solicitud no se borra (queda en la auditoría y en «Mis solicitudes»); el estudiante
 * queda libre para postularse de nuevo. Los datos existentes no cambian.
 *
 * Producción: ejecutar solo esta migración con `--path`.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', fn (Blueprint $t) => $t->enum('status', ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA'])->default('PENDIENTE')->change());
    }

    public function down(): void
    {
        // Sin el valor nuevo, una cancelada vuelve como RECHAZADA (no se pierde la fila ni se reactiva la solicitud).
        DB::table('requests')->where('status', 'CANCELADA')->update(['status' => 'RECHAZADA']);
        Schema::table('requests', fn (Blueprint $t) => $t->enum('status', ['PENDIENTE', 'APROBADA', 'RECHAZADA'])->default('PENDIENTE')->change());
    }
};
