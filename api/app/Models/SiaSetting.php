<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiaSetting extends Model
{
    protected $primaryKey = 'key';
    public    $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /* Límites anti-abuso. Etapa de pruebas (Jose, 2026-09-28): más margen por
       persona y varias cuentas de Groq en rotación. Cada cuenta gratis da 1000
       requests/día y 8000 tokens/min; per_key deja un 20 % de reserva. */
    public const DEFAULTS = [
        'guest_per_hour'           => 20,
        'guest_per_day'            => 40,
        'user_per_day'             => 60,
        'max_messages'             => 20,   // preguntas por conversación
        'max_question_chars'       => 500,
        'max_answer_tokens'        => 400,
        'global_requests_per_day'  => 2000,
        'global_tokens_per_day'    => 2500000,
        'per_key_requests_per_day' => 800,
        'enabled'                  => 1,
    ];

    public static function all_values(): array
    {
        $stored = static::query()->pluck('value', 'key')->all();
        $out = [];
        foreach (self::DEFAULTS as $k => $v) {
            $out[$k] = isset($stored[$k]) ? (int) $stored[$k] : $v;
        }
        return $out;
    }
}
