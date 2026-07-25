<?php # archivo: backend/database/migrations/2026_03_09_052333_create_seedbed_user_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('seedbed_user', function (Blueprint $table) {

            $table->id();

            $table->foreignId('seedbed_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('role',[
                'LIDER',
                'INVESTIGADOR',
                'AUXILIAR'
            ])->default('INVESTIGADOR');

            $table->timestamps();

        });

    }


    public function down(): void
    {
        Schema::dropIfExists('seedbed_user');
    }

};