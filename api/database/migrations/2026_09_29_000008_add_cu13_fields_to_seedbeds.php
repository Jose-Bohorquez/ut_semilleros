<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * CU13 (Registrar semillero) / RF13: campos que pedía la especificación y no
 * existían — code (único, RN08), grupo, CAT, coordinador, misión, visión,
 * justificación, objetivo general y la referencia de aprobación (RN03 /
 * RNF06). Todos nullable: los semilleros ya registrados no tienen estos
 * datos todavía.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->string('code')->nullable()->unique()->after('id');
            $table->foreignId('group_id')->nullable()->after('program_id')
                ->constrained('groups')->nullOnDelete();
            $table->foreignId('cat_id')->nullable()->after('group_id')
                ->constrained('cats')->nullOnDelete();
            $table->foreignId('coordinator_id')->nullable()->after('cat_id')
                ->constrained('coordinators')->nullOnDelete();
            $table->text('mision')->nullable()->after('description');
            $table->text('vision')->nullable()->after('mision');
            $table->text('justificacion')->nullable()->after('vision');
            $table->text('objetivo_general')->nullable()->after('justificacion');
            /* RN03 / RNF06: aprobación escrita del área administrativa */
            $table->string('authorization_reference')->nullable()->after('objetivo_general');
        });
    }

    public function down(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropConstrainedForeignId('cat_id');
            $table->dropConstrainedForeignId('coordinator_id');
            $table->dropColumn(['code', 'mision', 'vision', 'justificacion', 'objetivo_general', 'authorization_reference']);
        });
    }
};
