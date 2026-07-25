<?php # archivo:📁 backend/database/migrations/2026_03_09_233413_create_products_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {

            $table->id();

            $table->foreignId('project_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->enum('type',[
                'ARTICULO',
                'PONENCIA',
                'POSTER',
                'LIBRO',
                'SOFTWARE',
                'PROTOTIPO'
            ]);

            $table->string('title');

            $table->year('year')->nullable();

            $table->string('url')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
