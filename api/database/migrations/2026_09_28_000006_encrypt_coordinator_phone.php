<?php
// #archivo: /backend/database/migrations/2026_09_28_000006_encrypt_coordinator_phone.php
// RNF03 / RNF12: el teléfono del coordinador (dato personal) se cifra en reposo con
// APP_KEY (cast «encrypted» del modelo). El texto cifrado ocupa ~200 caracteres:
// la columna pasa a TEXT. Los teléfonos de los CAT son institucionales y no se cifran.

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('coordinators', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
        });

        DB::table('coordinators')->whereNotNull('phone')->where('phone', '!=', '')->orderBy('id')
            ->each(function ($row) {
                if ($this->isEncrypted($row->phone)) return;   /* idempotente */
                DB::table('coordinators')->where('id', $row->id)->update(['phone' => Crypt::encryptString($row->phone)]);
            });
    }

    public function down(): void
    {
        DB::table('coordinators')->whereNotNull('phone')->orderBy('id')
            ->each(function ($row) {
                if (!$this->isEncrypted($row->phone)) return;
                DB::table('coordinators')->where('id', $row->id)->update(['phone' => Crypt::decryptString($row->phone)]);
            });

        Schema::table('coordinators', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });
    }

    private function isEncrypted(string $value): bool
    {
        try { Crypt::decryptString($value); return true; } catch (\Throwable) { return false; }
    }
};
