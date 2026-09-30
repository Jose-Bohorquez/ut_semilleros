<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/* CU22: el formulario de "Ser miembro" pide programa, teléfono y mensaje.
   Ninguno de los 3 existía en requests. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('seedbed_id')->constrained()->nullOnDelete();
            $table->string('phone')->nullable()->after('status');
            $table->text('message')->nullable()->after('phone');
        });
    }

    public function down(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('program_id');
            $table->dropColumn(['phone', 'message']);
        });
    }
};
