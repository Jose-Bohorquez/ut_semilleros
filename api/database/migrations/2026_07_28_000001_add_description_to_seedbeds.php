<?php
// #archivo: /backend/database/migrations/2026_07_28_000001_add_description_to_seedbeds.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->text('description')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('seedbeds', function (Blueprint $table) {
            $table->dropColumn('description');
        });
    }
};
