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
        Schema::create('semillero_usuario', function (Blueprint $table) {
            $table->id();

            // 🔗 Relaciones
            $table->foreignId('semillero_id')
                  ->constrained('semilleros')
                  ->cascadeOnDelete();

            $table->foreignId('usuario_id')
                  ->constrained('users')
                  ->cascadeOnDelete();

            // 🧩 Rol dentro del semillero
            $table->enum('rol_en_semillero', [
                'coordinador',
                'integrante',
                'asistente',
                'invitado'
            ])->default('integrante');

            // 🕓 Estado de participación
            $table->enum('estado', ['activo', 'inactivo'])->default('activo');

            $table->timestamps();

            // 🔒 Evita duplicados del mismo usuario en el mismo semillero
            $table->unique(['semillero_id', 'usuario_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('semillero_usuario');
    }
};
