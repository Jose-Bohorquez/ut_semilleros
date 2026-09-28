<?php
// #archivo: /backend/database/migrations/2026_09_28_000003_add_code_to_faculties.php
// RF02 / RN08: la facultad se identifica por un código único (la tabla solo tenía nombre).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            /* Nullable solo para las facultades que ya existen (no hay un código
               real que inventarles); el controlador lo exige al crear y al editar.
               El índice único admite varios NULL en MySQL y SQLite. */
            $table->string('code', 20)->nullable()->unique()->after('id');
        });
    }

    public function down(): void
    {
        Schema::table('faculties', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn('code');
        });
    }
};
