<?php #archivo: backen/database/migrations/2026_03_11_004125_create_proposals_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('proposals', function (Blueprint $table) {

            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('title');

            $table->text('description');

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
        Schema::dropIfExists('proposals');
    }

};