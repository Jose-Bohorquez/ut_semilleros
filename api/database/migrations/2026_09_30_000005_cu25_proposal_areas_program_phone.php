<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('proposal_area', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proposal_id')->constrained()->cascadeOnDelete();
            $table->foreignId('area_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['proposal_id', 'area_id']);
        });

        /* Migra el area_id único que ya tenía cada propuesta (CU25: ahora
           selección múltiple, mismo patrón que seedbed_area en CU13 Ronda B). */
        DB::table('proposals')->whereNotNull('area_id')->orderBy('id')->each(function ($proposal) {
            DB::table('proposal_area')->insert([
                'proposal_id' => $proposal->id,
                'area_id'     => $proposal->area_id,
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('program_id')->nullable()->after('user_id')->constrained();
            $table->text('phone')->nullable()->after('description');
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('proposals', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('user_id')->constrained();
        });

        DB::table('proposal_area')->orderBy('id')->each(function ($row) {
            DB::table('proposals')->where('id', $row->proposal_id)->update(['area_id' => $row->area_id]);
        });

        Schema::table('proposals', function (Blueprint $table) {
            $table->dropForeign(['program_id']);
            $table->dropColumn(['program_id', 'phone']);
        });

        Schema::dropIfExists('proposal_area');
    }
};
