<?php
// archivo: backend/database/migrations/2026_03_10_235811_create_objectives_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('objectives', function (Blueprint $table) {

            $table->id();

            $table->foreignId('seedbed_id')
                ->constrained('seedbeds')
                ->cascadeOnDelete();

            $table->text('content');

            $table->timestamps();

        });

    }


    public function down(): void
    {
        Schema::dropIfExists('objectives');
    }

};