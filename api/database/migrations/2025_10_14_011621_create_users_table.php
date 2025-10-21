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
        Schema::create('users', function (Blueprint $table) {
            $table->id();

            // 🔹 Datos de identificación
            $table->foreignId('tipo_documento_id')
                ->constrained('tipos_documento')
                ->onDelete('restrict');

            $table->string('numero_documento', 20)->unique();

            // 🔹 Nombres y apellidos
            $table->string('primer_nombre', 50);
            $table->string('segundo_nombre', 50)->nullable();
            $table->string('primer_apellido', 50);
            $table->string('segundo_apellido', 50)->nullable();

            // 🔹 Correos y autenticación
            $table->string('correo_personal', 100)->unique();
            $table->string('correo_institucional', 100)->nullable()->unique();
            $table->string('username', 50)->unique();
            $table->string('password');

            // 🔹 Datos académicos
            $table->foreignId('programa_id')
                ->nullable()
                ->constrained('programas')
                ->onDelete('set null');

            $table->tinyInteger('semestre')->nullable();

            // 🔹 Datos de contacto
            $table->string('direccion', 150)->nullable();
            $table->foreignId('ciudad_id')
                ->nullable()
                ->constrained('ciudades')
                ->onDelete('set null');

            // 🔹 Estado y auditoría
            $table->enum('estado', ['Activo', 'Inactivo', 'Suspendido'])->default('Activo');
            $table->timestamp('fecha_cambio_estado')->nullable();

            $table->timestamps();
            $table->softDeletes(); // para borrado lógico
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
