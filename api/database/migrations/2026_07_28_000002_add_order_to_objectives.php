<?php
// #archivo: /backend/database/migrations/2026_07_28_000002_add_order_to_objectives.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->unsignedInteger('order')->default(0)->after('content');
        });
    }

    public function down(): void
    {
        Schema::table('objectives', function (Blueprint $table) {
            $table->dropColumn('order');
        });
    }
};
