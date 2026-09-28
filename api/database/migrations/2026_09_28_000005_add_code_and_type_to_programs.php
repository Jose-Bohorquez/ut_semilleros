<?php
// #archivo: /backend/database/migrations/2026_09_28_000005_add_code_and_type_to_programs.php
// RF03 / RN08: el programa tiene código único y tipo (Pregrado / Posgrado).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            /* Nullable solo para los programas que ya existen; el controlador
               exige ambos al crear y al editar (igual que el código de facultad). */
            $table->string('code', 20)->nullable()->unique()->after('id');
            $table->enum('type', ['PREGRADO', 'POSGRADO'])->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('programs', function (Blueprint $table) {
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'type']);
        });
    }
};
