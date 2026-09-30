<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/* CU22-H1 (RNF03/RNF12): requests.phone era el único teléfono de estudiante en
   texto plano. Un teléfono de 10 dígitos cifrado ocupa ~200 caracteres y con 15
   dígitos se acerca al límite de string(255), así que la columna pasa a text.

   IMPORTANTE: cifra con APP_KEY. Debe correr con la MISMA clave con la que luego se
   descifrará. Si encuentra un valor que YA tiene formato de cifrado pero no se puede
   descifrar (clave distinta), aborta en vez de cifrarlo otra vez y volverlo
   irrecuperable. Respaldar la tabla `requests` antes de ejecutarla en producción. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('requests', function (Blueprint $table) {
            $table->text('phone')->nullable()->change();
        });

        /* Un teléfono vacío no aporta nada y con el cast `encrypted` no se podría leer:
           se guarda como NULL. */
        DB::table('requests')->where('phone', '')->update(['phone' => null]);

        /* Idempotente: si el valor ya descifra, ya está cifrado y no se toca.
           DB::table + Crypt directo para no disparar el cast ni el observer. */
        DB::table('requests')->whereNotNull('phone')->orderBy('id')->each(function ($row) {
            if (self::isEncrypted($row->phone)) return;
            self::abortIfForeignPayload($row->phone, $row->id);
            DB::table('requests')->where('id', $row->id)
                ->update(['phone' => Crypt::encryptString($row->phone)]);
        });
    }

    public function down(): void
    {
        DB::table('requests')->whereNotNull('phone')->orderBy('id')->each(function ($row) {
            if (self::isEncrypted($row->phone)) {
                DB::table('requests')->where('id', $row->id)
                    ->update(['phone' => Crypt::decryptString($row->phone)]);
                return;
            }
            self::abortIfForeignPayload($row->phone, $row->id);
        });

        Schema::table('requests', function (Blueprint $table) {
            $table->string('phone')->nullable()->change();
        });
    }

    private static function isEncrypted(string $value): bool
    {
        try {
            Crypt::decryptString($value);
            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /* Formato de Laravel: base64 de un JSON con iv, value y mac. Si lo tiene y no se
       pudo descifrar, fue cifrado con otra APP_KEY. */
    private static function abortIfForeignPayload(string $value, $id): void
    {
        $json = json_decode((string) base64_decode($value, true), true);
        if (is_array($json) && isset($json['iv'], $json['value'], $json['mac'])) {
            throw new \RuntimeException(
                "requests.id={$id}: el teléfono ya está cifrado con otra APP_KEY; se aborta para no perderlo."
            );
        }
    }
};
