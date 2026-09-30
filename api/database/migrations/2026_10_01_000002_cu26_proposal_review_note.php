<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* CU26 (RF11): «Mis propuestas» muestra la observación del evaluador y CU26
   paso 5 la llama «la respuesta». No existía ninguna columna para guardarla.
   Cambio puramente aditivo: nullable, sin tocar datos ni el enum de estados
   (el vocabulario Recibida/Viable/Archivada se expone como etiqueta, ver
   Proposal::STATUS_LABELS). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->text('review_note')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn('review_note');
        });
    }
};
