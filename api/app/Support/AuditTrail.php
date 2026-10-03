<?php

namespace App\Support;

use App\Models\Audit;
use App\Models\Notificacion;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * CU29 «Registrar auditoría» (RF14, RN07): único punto por el que el sistema escribe en `audits`.
 *
 * Qué guarda: quién, qué colección (tabla) y documento, qué acción (CREATE, UPDATE,
 * STATUS_CHANGE, DELETE, RESTORE, LOGIN…), los valores anteriores y nuevos de los campos que
 * cambiaron y la IP.
 *
 * Qué NO guarda (paso 2 del flujo): contraseñas, tokens ni la foto de perfil, y los campos
 * cifrados con APP_KEY (teléfono, dirección…) quedan enmascarados — registrar su valor en claro
 * en `audits` anularía el cifrado en reposo (RNF03/RNF12).
 *
 * E1: si falla el registro, el evento va al log técnico, se avisa al Administrador y la
 * operación principal NO se revierte. Única excepción deliberada: el inicio de sesión (`strict`),
 * donde la decisión de CU01 es no emitir el token si no se pudo auditar.
 */
class AuditTrail
{
    /** Nunca se guardan en old_values/new_values. */
    private const EXCLUDED = ['password', 'remember_token', 'google_id', 'profile_photo', 'created_at', 'updated_at'];

    /** Si solo cambian estos campos no se audita: el inicio de sesión ya queda como evento LOGIN. */
    public const NOISE = ['last_login_at', 'updated_at', 'remember_token'];

    public const MASK = '[cifrado]';

    private const MAX_STRING = 1000;

    /** Valores seguros para guardar de un modelo (sin secretos, con lo cifrado enmascarado). */
    public static function sanitize(Model $model, array $attributes): array
    {
        $hidden = $model->getHidden();
        $casts  = $model->getCasts();
        $out    = [];

        foreach ($attributes as $key => $value) {
            if (in_array($key, self::EXCLUDED, true)
                || in_array($key, $hidden, true)
                || preg_match('/(password|token|secret)/i', (string) $key)) {
                continue;
            }
            if (isset($casts[$key]) && str_starts_with((string) $casts[$key], 'encrypted')) {
                $out[$key] = $value === null ? null : self::MASK;
                continue;
            }
            $out[$key] = is_string($value) && mb_strlen($value) > self::MAX_STRING
                ? mb_substr($value, 0, self::MAX_STRING) . '…'
                : $value;
        }

        return $out;
    }

    /**
     * Registra una fila de auditoría.
     *
     * @param  bool  $strict  true = si falla, relanza la excepción (solo LOGIN).
     */
    public static function record(
        string $action,
        string $table,
        $recordId,
        ?array $old = null,
        ?array $new = null,
        ?int $userId = null,
        bool $strict = false,
    ): ?Audit {
        try {
            return Audit::create([
                'user_id'    => $userId ?? Auth::id(),
                'action'     => $action,
                'table_name' => $table,
                'record_id'  => $recordId ?? 0,
                'old_values' => $old ?: null,
                'new_values' => $new ?: null,
                'ip_address' => self::ip(),
            ]);
        } catch (\Throwable $e) {
            if ($strict) {
                throw $e;
            }
            self::failed($e, ['action' => $action, 'table' => $table, 'record_id' => $recordId, 'user_id' => $userId ?? Auth::id()]);
            return null;
        }
    }

    /** Evento sin valores (LOGIN, LOGOUT, CONSENT, PASSWORD_RESET): usuario, acción, IP y fecha (A1). */
    public static function event(string $action, string $table, $recordId, ?int $userId = null, bool $strict = false): ?Audit
    {
        return self::record($action, $table, $recordId, null, null, $userId, $strict);
    }

    /**
     * Cambio en una relación muchos-a-muchos (áreas de una propuesta, programas de un semillero,
     * integrantes de un proyecto…): las tablas pivote no tienen modelo, así que el cambio se
     * registra como UPDATE del documento padre con la lista de ids antes y después.
     */
    public static function pivot(Model $model, string $relation, array $before, array $after): void
    {
        $b = array_values(array_unique(array_map('intval', $before)));
        $a = array_values(array_unique(array_map('intval', $after)));
        sort($b);
        sort($a);
        if ($b === $a) {
            return;
        }
        self::record('UPDATE', $model->getTable(), $model->getKey(), [$relation => $b], [$relation => $a]);
    }

    private static function ip(): ?string
    {
        try {
            return request()?->ip();
        } catch (\Throwable) {
            return null;
        }
    }

    /** E1: log técnico + alerta al Administrador, sin propagar la excepción. */
    private static function failed(\Throwable $e, array $context): void
    {
        try {
            Log::error('[AUDIT] No se pudo registrar el evento de auditoría', $context + ['error' => $e->getMessage()]);
        } catch (\Throwable) {
            // el log tampoco responde: no hay más que hacer sin romper la operación principal
        }
        self::alertAdministrator();
    }

    /** Una sola notificación cada 10 minutos para no inundar al Administrador si la falla persiste. */
    private static function alertAdministrator(): void
    {
        try {
            if (!Cache::add('audit:failure-alert', 1, now()->addMinutes(10))) {
                return;
            }
            $admin = User::where('role', 'ADMIN_SISTEMA')->where('status', 'ACTIVO')->orderBy('id')->first();
            if (!$admin) {
                return;
            }
            Notificacion::create([
                'title'        => 'Falla en el registro de auditoría',
                'message'      => 'No se pudo registrar un evento de auditoría (la operación del usuario sí se completó). Revisa el log técnico del servidor.',
                'type'         => 'ANUNCIO',
                'created_by'   => $admin->id,
                'target_type'  => 'ROLE',
                'target_value' => 'ADMIN_SISTEMA',
            ]);
        } catch (\Throwable $e) {
            try {
                Log::error('[AUDIT] Tampoco se pudo alertar al Administrador', ['error' => $e->getMessage()]);
            } catch (\Throwable) {
            }
        }
    }
}
