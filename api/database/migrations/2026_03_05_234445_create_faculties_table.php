<?php // #archivo: /backend/html/database/migrations/create_faculties_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migration: create_faculties_table
 *
 * Tabla que almacena las facultades de la universidad.
 * Se utiliza para estructurar programas académicos y semilleros.
 *
 * Alineado con:
 * RF02 - Gestión de Facultades
 */
return new class extends Migration
{
    /**
     * Se ejecuta al correr:
     * php artisan migrate
     */
    public function up(): void
    {
        Schema::create('faculties', function (Blueprint $table) {

            // ID principal
            $table->id();

            // Nombre de la facultad
            $table->string('name');

            // Estado lógico
            $table->enum('status', [
                'ACTIVO',
                'INACTIVO'
            ])->default('ACTIVO');

            // Auditoría básica
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Se ejecuta si hacemos rollback
     */
    public function down(): void
    {
        Schema::dropIfExists('faculties');
    }
};