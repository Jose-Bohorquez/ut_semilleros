<?php #archivo backend/datyabase/migration/2026_03_10_234950_create_groups_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {

        Schema::create('groups', function (Blueprint $table) {

            $table->id();

            $table->string('name');

            $table->string('code')->unique();

            $table->timestamps();

        });

    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }

};