<?php #archivo: backend/app/Http/Middleware/EnsureDataConsent.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureDataConsent
{
    /* Lo mínimo para mostrar el aviso, aceptarlo o salir */
    private const ALLOWED = ['api/me', 'api/consent', 'api/logout'];

    /**
     * RF16 / RN09: ningún estudiante usa la aplicación sin la autorización de
     * tratamiento de datos registrada. Se aplica en el servidor (no solo en la
     * pantalla) para que no se pueda saltar llamando a la API directamente.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user
            && $user->role === 'ESTUDIANTE'
            && !$user->data_consent_at
            && !in_array($request->path(), self::ALLOWED, true)
        ) {
            return response()->json([
                'message' => 'Debe aceptar la autorización de tratamiento de datos personales para usar la aplicación.',
                'code'    => 'CONSENT_REQUIRED',
            ], 403);
        }

        return $next($request);
    }
}
