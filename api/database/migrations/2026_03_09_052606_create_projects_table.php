<?php # archivo: backend/database/migrations/2026_03_09_052606_create_projects_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('projects', function (Blueprint $table) {

            $table->id();

            $table->string('title');

            $table->text('description')->nullable();

            $table->foreignId('seedbed_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('status',[
                'ACTIVO',
                'FINALIZADO',
                'SUSPENDIDO'
            ])->default('ACTIVO');

            $table->timestamps();

        });

    }


    public function down(): void
    {
        Schema::dropIfExists('projects');
    }

};