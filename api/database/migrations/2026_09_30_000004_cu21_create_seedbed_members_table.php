<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seedbed_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seedbed_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name');
            $table->string('student_code');
            $table->foreignId('program_id')->constrained();
            $table->string('level');
            $table->string('email');
            $table->text('address')->nullable();
            $table->text('phone')->nullable();
            $table->string('status')->default('ACTIVO');
            $table->string('inactivation_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seedbed_members');
    }
};
