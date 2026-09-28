<?php
// #archivo: /backend/database/migrations/2026_09_28_000001_create_sia_tables.php
// SIA — Sistema Integrado de Asistencia (RF17 propuesto, 2026-09-28).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        /* Una conversación = una interacción completa con SIA. La calificación
           (1–5 caritas) y el comentario se piden al finalizarla. */
        Schema::create('sia_conversations', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique();              // identifica la conversación ante el cliente
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_hash', 64)->index();              // IP con hash (no se guarda la IP en claro)
            $table->string('page', 120)->nullable();             // pantalla desde la que se abrió
            $table->enum('status', ['OPEN', 'CLOSED'])->default('OPEN');
            $table->unsignedSmallInteger('message_count')->default(0);
            $table->unsignedTinyInteger('rating')->nullable();   // 1..5 (😞 … 😄); null = sin calificar
            $table->boolean('rating_skipped')->default(false);
            $table->text('feedback')->nullable();                // comentario libre del usuario
            $table->text('admin_note')->nullable();              // revisión del administrador
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('sia_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('conversation_id')->constrained('sia_conversations')->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->enum('status', ['OK', 'LIMITED', 'ERROR'])->default('OK');
            $table->unsignedInteger('prompt_tokens')->default(0);
            $table->unsignedInteger('completion_tokens')->default(0);
            $table->unsignedInteger('latency_ms')->default(0);
            $table->string('model', 60)->nullable();
            $table->timestamps();
            $table->index(['created_at', 'role']);
        });

        /* Respuestas corregidas por el administrador: entran al contexto de SIA
           sin necesidad de desplegar (ajuste a partir del feedback). */
        Schema::create('sia_knowledge', function (Blueprint $table) {
            $table->id();
            $table->string('question', 300);
            $table->text('answer');
            $table->boolean('active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('sia_settings', function (Blueprint $table) {
            $table->string('key', 60)->primary();
            $table->string('value', 255);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sia_settings');
        Schema::dropIfExists('sia_knowledge');
        Schema::dropIfExists('sia_messages');
        Schema::dropIfExists('sia_conversations');
    }
};
