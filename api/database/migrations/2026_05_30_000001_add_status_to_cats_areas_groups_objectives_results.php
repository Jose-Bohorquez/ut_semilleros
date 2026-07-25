<?php
// archivo: backend/database/migrations/2026_05_30_000001_add_status_to_cats_areas_groups_objectives_results.php
// RF04, RF05, RF06, RF08, RF09: "solo cambia de estado" — agregar campo status

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['cats', 'areas', 'groups', 'objectives', 'results'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->enum('status', ['ACTIVO', 'INACTIVO'])
                    ->default('ACTIVO')
                    ->after('id');
            });
        }
    }

    public function down(): void
    {
        foreach (['cats', 'areas', 'groups', 'objectives', 'results'] as $table) {
            Schema::table($table, function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
    }
};
