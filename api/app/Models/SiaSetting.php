<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SiaSetting extends Model
{
    protected $primaryKey = 'key';
    public    $incrementing = false;
    protected $keyType = 'string';
    protected $fillable = ['key', 'value'];

    /* Límites anti-abuso (decisión de Jose, 2026-09-28: «moderados»).
       El cupo gratis de Groq para gpt-oss-20b es 1000 requests/día y 8000 tokens/min. */
    public const DEFAULTS = [
        'guest_per_hour'         => 10,
        'guest_per_day'          => 20,
        'user_per_day'           => 30,
        'max_messages'           => 12,   // preguntas por conversación
        'max_question_chars'     => 500,
        'max_answer_tokens'      => 400,
        'global_requests_per_day'=> 600,
        'global_tokens_per_day'  => 400000,
        'enabled'                => 1,
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
