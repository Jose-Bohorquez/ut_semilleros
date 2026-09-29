<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Grupos de permisos (v2 del RBAC granular, 2026-09-29): personas de
 * distintos roles agrupadas (ej. "Comité editorial") a las que se les puede
 * dar permisos como conjunto, sin tocar su rol individual. Un grupo solo
 * OTORGA permisos (no revoca) — para quitar algo a alguien se usa la
 * excepción por persona (user_permissions) que ya existía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permission_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permission_group_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['group_id', 'user_id']);
        });

        Schema::create('permission_group_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('permission_groups')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['group_id', 'permission_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_group_permissions');
        Schema::dropIfExists('permission_group_user');
        Schema::dropIfExists('permission_groups');
    }
};
