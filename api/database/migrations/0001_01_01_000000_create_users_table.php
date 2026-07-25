<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: Creación de la tabla users y tablas relacionadas a autenticación.
 *
 * Esta migración cumple con:
 * - RF01: Gestión de usuarios
 * - CU01: Ciclo de vida del usuario
 * - RF14: Soporte para auditoría
 *
 * Contiene:
 * - Tabla users
 * - Tabla password_reset_tokens
 * - Tabla sessions
 */
return new class extends Migration
{
    /**
     * Método up()
     *
     * Se ejecuta cuando usamos:
     * php artisan migrate
     *
     * Su función es CREAR las tablas en la base de datos.
     */
    public function up(): void
    {
        /**
         * TABLA: users
         *
         * Tabla principal del sistema para autenticación y autorización.
         */
        Schema::create('users', function (Blueprint $table) {

            // ID autoincremental (clave primaria)
            $table->id();

            // Nombre completo del usuario
            $table->string('name');

            // Correo electrónico único
            $table->string('email')->unique();

            // Fecha de verificación del correo (opcional)
            $table->timestamp('email_verified_at')->nullable();

            // Contraseña encriptada (hash)
            $table->string('password');

            /**
             * Campo role
             * Define el rol dentro del sistema.
             * Alineado con CU01.
             */
            $table->enum('role', [
                'ADMIN_SISTEMA',
                'ADMINISTRATIVO',
                'LIDER_SEMILLERO',
                'ESTUDIANTE'
            ])->default('ESTUDIANTE');

            /**
             * Campo status
             * Permite activar o desactivar usuarios.
             * No se elimina físicamente el usuario (RF01).
             */
            $table->enum('status', [
                'ACTIVO',
                'INACTIVO'
            ])->default('ACTIVO');

            /**
             * Campos de auditoría (RF14)
             * Permiten saber quién creó o modificó el registro.
             */
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            // Token para funcionalidad "Recordarme"
            $table->rememberToken();

            // Campos created_at y updated_at
            $table->timestamps();
        });

        /**
         * TABLA: password_reset_tokens
         *
         * Almacena tokens temporales para recuperación de contraseña.
         */
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary(); // Email del usuario
            $table->string('token'); // Token de recuperación
            $table->timestamp('created_at')->nullable(); // Fecha de creación
        });

        /**
         * TABLA: sessions
         *
         * Almacena información de sesiones cuando el driver
         * de sesión es base de datos.
         */
        Schema::create('sessions', function (Blueprint $table) {

            // ID único de sesión
            $table->string('id')->primary();

            // Usuario asociado a la sesión (si existe)
            $table->foreignId('user_id')->nullable()->index();

            // Dirección IP del cliente
            $table->string('ip_address', 45)->nullable();

            // Información del navegador/dispositivo
            $table->text('user_agent')->nullable();

            // Datos serializados de la sesión
            $table->longText('payload');

            // Última actividad registrada
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Método down()
     *
     * Se ejecuta cuando usamos:
     * php artisan migrate:rollback
     * o
     * php artisan migrate:fresh
     *
     * Su función es ELIMINAR las tablas creadas en el método up().
     */
    public function down(): void
    {
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('users');
    }
};