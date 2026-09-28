<?php
// #archivo: /backend/database/migrations/2026_09_28_000004_add_google_and_consent_to_users.php
// CU02: ingreso con Google (google_id = claim "sub", estable aunque cambie el correo).
// RF16 / RN09: fecha de aceptación de la autorización de tratamiento de datos (Ley 1581).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('google_id', 64)->nullable()->unique()->after('email');
            $table->timestamp('data_consent_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['google_id']);
            $table->dropColumn(['google_id', 'data_consent_at']);
        });
    }
};
