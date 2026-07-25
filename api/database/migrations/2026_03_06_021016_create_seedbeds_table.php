<?php
// #archivo: /backend/database/migrations/create_seedbeds_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::create('seedbeds', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            /**
             * Relación con programas
             */
            $table->foreignId('program_id')
                ->constrained('programs')
                ->cascadeOnDelete();

            $table->enum('status', [
                'ACTIVO',
                'INACTIVO'
            ])->default('ACTIVO');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seedbeds');
    }
};