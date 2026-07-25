<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('notificaciones', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->enum('type', ['ANUNCIO', 'RECORDATORIO'])->default('ANUNCIO');
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->string('link', 500)->nullable();

            /*
            | target_type  | target_value
            |--------------|-------------------------------------------
            | ALL          | null  — todos los usuarios
            | ROLE         | nombre del rol (e.g. 'ESTUDIANTE')
            | SEEDBED      | id del semillero (string)
            | USER         | id del usuario (string)
            */
            $table->enum('target_type', ['ALL', 'ROLE', 'SEEDBED', 'USER'])->default('ALL');
            $table->string('target_value', 255)->nullable();

            $table->timestamps();
        });

        Schema::create('notificacion_reads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notificacion_id')
                  ->constrained('notificaciones')
                  ->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('read_at')->useCurrent();
            $table->unique(['notificacion_id', 'user_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notificacion_reads');
        Schema::dropIfExists('notificaciones');
    }
};
