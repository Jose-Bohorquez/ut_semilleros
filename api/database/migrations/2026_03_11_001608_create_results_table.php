<?php  #archivo : bakcend/database/migrations/2026_03_11_001608_create_results_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('results', function (Blueprint $table) {

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
        Schema::dropIfExists('results');
    }

};