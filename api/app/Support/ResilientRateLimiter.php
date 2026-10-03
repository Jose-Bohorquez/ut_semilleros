<?php

namespace App\Support;

use Illuminate\Cache\RateLimiter;
use Illuminate\Database\QueryException;

/**
 * Limitador de peticiones que no tumba la petición si la tabla `cache` entra en deadlock.
 *
 * El contador del límite vive en la caché (driver `database`). Una pantalla como el Dashboard
 * lanza ~12 peticiones en paralelo del mismo usuario y todas intentan el mismo
 * `insert ignore into cache`, por lo que MySQL aborta alguna con «1213 Deadlock found» y el
 * usuario veía un 500 aleatorio. Se reintenta una vez y, si persiste, se deja pasar la petición:
 * contar mal una vez es mucho menos grave que fallarle al usuario.
 */
class ResilientRateLimiter extends RateLimiter
{
    public function hit($key, $decaySeconds = 60)
    {
        return $this->tolerant(fn () => parent::hit($key, $decaySeconds), 1);
    }

    public function attempts($key)
    {
        return $this->tolerant(fn () => parent::attempts($key), 0);
    }

    public function availableIn($key)
    {
        return $this->tolerant(fn () => parent::availableIn($key), 0);
    }

    private function tolerant(callable $fn, int $fallback)
    {
        for ($try = 0; $try < 2; $try++) {
            try {
                return $fn();
            } catch (QueryException $e) {
                if (! self::isLockContention($e)) {
                    throw $e;
                }
                usleep(50_000);
            }
        }

        try {
            report(new \RuntimeException('Limitador de peticiones: deadlock persistente en la caché; se deja pasar la petición.'));
        } catch (\Throwable) {
            // sin contenedor de aplicación (pruebas unitarias): nada que registrar
        }

        return $fallback;
    }

    public static function isLockContention(\Throwable $e): bool
    {
        $m = $e->getMessage();

        return str_contains($m, '1213') || str_contains($m, '1205') || str_contains($m, '40001')
            || stripos($m, 'deadlock') !== false || stripos($m, 'lock wait timeout') !== false;
    }
}
