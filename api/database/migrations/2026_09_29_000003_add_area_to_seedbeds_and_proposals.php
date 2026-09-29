<?php
// #archivo: /backend/database/migrations/2026_09_29_000003_add_area_to_seedbeds_and_proposals.php
// RF05: «todo semillero y toda propuesta tiene al menos un área». Nullable
// solo para los registros que ya existían; el controlador la exige al crear
// y al editar (mismo patrón que faculties.code / programs.code).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('program_id')->constrained('areas')->nullOnDelete();
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('user_id')->constrained('areas')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
        });
    }
};
