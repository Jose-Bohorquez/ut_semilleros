<?php #archivo: backend/database/migrations/2026_03_11_002126_create_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('requests', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('seedbed_id')
                ->constrained('seedbeds')
                ->cascadeOnDelete();

            $table->enum('status',[
                'PENDIENTE',
                'APROBADA',
                'RECHAZADA'
            ])->default('PENDIENTE');

            $table->timestamps();

        });

    }

    public function down(): void
    {
        Schema::dropIfExists('requests');
    }

};