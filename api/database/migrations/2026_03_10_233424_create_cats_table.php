<?php # archivo: vackend/database/migrations/2026_03_10_233424_create_cats_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('cats', function (Blueprint $table) {

            $table->id();

            $table->string('name');
            $table->string('code')->unique();

            $table->string('address')->nullable();
            $table->string('city')->nullable();

            $table->string('phone1')->nullable();
            $table->string('phone2')->nullable();
            $table->string('phone3')->nullable();

            $table->timestamps();

        });

    }


    public function down(): void
    {
        Schema::dropIfExists('cats');
    }

};