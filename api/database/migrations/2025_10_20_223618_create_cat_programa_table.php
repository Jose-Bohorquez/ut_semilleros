<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cat_programa', function (Blueprint $table) {
            $table->id();

            // 🔗 Relaciones principales
            $table->foreignId('centro_tutorial_id')
                  ->constrained('centros_tutoriales')
                  ->cascadeOnDelete();

            $table->foreignId('programa_id')
                  ->constrained('programas')
                  ->cascadeOnDelete();

            // 🕓 Datos adicionales
            $table->string('jornada', 50)->nullable();     // Ejemplo: Diurna, Nocturna, Fines de Semana
            $table->string('modalidad', 50)->nullable();   // Ejemplo: Presencial, Virtual, Distancia

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cat_programa');
    }
};
