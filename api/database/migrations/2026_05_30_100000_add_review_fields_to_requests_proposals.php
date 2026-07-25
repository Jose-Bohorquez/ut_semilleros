<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('requests', function (Blueprint $table) {
            if (!Schema::hasColumn('requests', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
        Schema::table('proposals', function (Blueprint $table) {
            if (!Schema::hasColumn('proposals', 'reviewed_by')) {
                $table->unsignedBigInteger('reviewed_by')->nullable()->after('status');
                $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
            }
        });
    }
    public function down(): void {
        Schema::table('requests', function (Blueprint $table) {
            $table->dropColumn(['reviewed_by', 'reviewed_at']);
        });
        Schema::table('proposals', function (Blueprint $table) {
            $table->dropColumn(['reviewed_by', 'reviewed_at']);
        });
    }
};
