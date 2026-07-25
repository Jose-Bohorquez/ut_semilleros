<?php
// #archivo: /backend/database/migrations/create_programs_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tabla de programas académicos
 * Relacionada con facultades
 */
return new class extends Migration {

    public function up(): void
    {

        Schema::create('programs', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            /**
             * Relación con facultades
             */
            $table->foreignId('faculty_id')
                ->constrained('faculties')
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
        Schema::dropIfExists('programs');
    }

};