<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* CU15: al inactivar un semillero se registra el motivo, y las solicitudes
   pendientes se rechazan automáticamente con una razón. Ninguna de las 2
   columnas existía. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->text('inactivation_reason')->nullable()->after('status');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->text('reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->dropColumn('inactivation_reason');
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn('reason');
        });
    }
};
