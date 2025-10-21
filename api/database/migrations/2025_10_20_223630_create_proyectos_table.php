<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('titulo', 200); // Título del proyecto
            $table->text('descripcion')->nullable(); // Descripción breve o resumen
            $table->foreignId('semillero_id')
                  ->constrained('semilleros')
                  ->onDelete('cascade'); // Cada proyecto pertenece a un semillero
            $table->enum('estado', ['Activo', 'Finalizado', 'En Progreso'])->default('Activo');
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyectos');
    }
};
