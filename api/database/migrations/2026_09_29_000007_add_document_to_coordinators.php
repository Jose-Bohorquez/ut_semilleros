<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU12 / RF07: "número de documento" del coordinador, único (RN08). Nullable
 * al inicio: coordinadores ya registrados no tienen este dato todavía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->string('document')->nullable()->unique()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->dropColumn('document');
        });
    }
};
