<?php
// #archivo: /backend/database/migrations/2026_09_28_000002_add_key_and_source_to_sia_messages.php
// SIA: cuenta de Groq usada (etiqueta, nunca la key) y origen de la respuesta.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('sia_messages', function (Blueprint $table) {
            $table->string('key_label', 20)->nullable()->after('model');       // cta_01, cta_02… (solo la etiqueta)
            $table->string('source', 10)->default('API')->after('key_label');  // API | LOCAL | FAQ
            $table->index(['key_label', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('sia_messages', function (Blueprint $table) {
            $table->dropIndex(['key_label', 'created_at']);
            $table->dropColumn(['key_label', 'source']);
        });
    }
};
