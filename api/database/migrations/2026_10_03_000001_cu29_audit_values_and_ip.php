<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* CU29 (RF14, RN07): la auditoría debe guardar «los valores anteriores y nuevos de los campos
   modificados» y «desde qué IP». La tabla solo tenía usuario, acción, tabla y registro.
   Cambio puramente aditivo: las filas existentes quedan con NULL en las columnas nuevas (la
   interfaz debe tolerarlo). Los índices sirven a los filtros de CU30 (usuario, tabla, acción
   y rango de fechas) y a la búsqueda del historial de un registro. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->json('old_values')->nullable()->after('record_id');
            $table->json('new_values')->nullable()->after('old_values');
            $table->string('ip_address', 45)->nullable()->after('new_values');   // IPv6 cabe en 45

            $table->index(['table_name', 'record_id'], 'audits_table_record_idx');
            $table->index('action', 'audits_action_idx');
            $table->index('created_at', 'audits_created_at_idx');
        });
    }

    public function down(): void
    {
        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex('audits_table_record_idx');
            $table->dropIndex('audits_action_idx');
            $table->dropIndex('audits_created_at_idx');
            $table->dropColumn(['old_values', 'new_values', 'ip_address']);
        });
    }
};
