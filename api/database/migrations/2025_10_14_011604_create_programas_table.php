<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('programas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150);
            $table->string('codigo', 20)->nullable()->unique();
            $table->text('descripcion')->nullable();

            // Nuevo 👇
            $table->string('nivel', 50)->nullable();      // Ej: Profesional, Tecnólogo
            $table->string('modalidad', 50)->nullable();  // Ej: Presencial, Virtual, Distancia

            // Relación con facultad
            $table->foreignId('facultad_id')
                ->constrained('facultades')
                ->onDelete('cascade');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('programas');
    }
};
