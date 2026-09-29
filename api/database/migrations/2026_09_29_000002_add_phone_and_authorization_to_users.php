<?php
// #archivo: /backend/database/migrations/2026_09_29_000002_add_phone_and_authorization_to_users.php
// CU05: el perfil muestra y permite editar el teléfono (RNF12: dato personal,
// se cifra en reposo igual que el teléfono del coordinador).
// RNF05 / RN02: todo usuario del panel web (no ESTUDIANTE) se crea con la
// referencia del comunicado que autorizó su alta, registrada en el usuario.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->text('phone')->nullable()->after('email');
            $table->string('authorization_reference', 255)->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'authorization_reference']);
        });
    }
};
