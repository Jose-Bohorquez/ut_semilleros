<?php
// #archivo: /backend/database/migrations/2026_09_29_000001_add_email_to_cats.php
// RF04: el CAT también registra un correo de contacto (código, nombre,
// dirección, ciudad, correo, teléfono principal y hasta 2 adicionales).

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('cats', function (Blueprint $table) {
            $table->string('email')->nullable()->after('city');
        });
    }

    public function down(): void
    {
        Schema::table('cats', function (Blueprint $table) {
            $table->dropColumn('email');
        });
    }
};
