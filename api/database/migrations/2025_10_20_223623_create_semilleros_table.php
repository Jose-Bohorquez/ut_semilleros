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
        Schema::create('semilleros', function (Blueprint $table) {
            $table->id();

            // 🧩 Datos principales del semillero
            $table->string('nombre');
            $table->text('descripcion')->nullable();
            $table->string('codigo', 20)->unique()->nullable();

            // 🔗 Relaciones
            $table->foreignId('programa_id')
                  ->constrained('programas')
                  ->cascadeOnDelete();

            $table->foreignId('docente_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();

            $table->foreignId('centro_tutorial_id')
                  ->nullable()
                  ->constrained('centros_tutoriales')
                  ->nullOnDelete();

            // ⚙️ Atributos adicionales
            $table->string('modalidad', 30)->default('Presencial'); // presencial, virtual, mixta
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');
            $table->date('fecha_creacion')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semilleros');
    }
};
