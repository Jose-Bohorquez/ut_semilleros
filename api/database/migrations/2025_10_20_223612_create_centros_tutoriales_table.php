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
        Schema::create('centros_tutoriales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre'); // Ej: "CAT Kennedy"
            $table->string('direccion')->nullable();
            $table->string('telefono', 20)->nullable();
            $table->string('email')->nullable();

            // Relación con la tabla ciudades
            $table->foreignId('ciudad_id')
                  ->nullable()
                  ->constrained('ciudades')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('centros_tutoriales');
    }
};
