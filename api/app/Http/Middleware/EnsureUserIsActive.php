<?php #archivo: backend/app/Http/Middleware/EnsureUserIsActive.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Rechaza cualquier request autenticado de un usuario INACTIVO.
     *
     * Hallazgo C-04 (2026-09-27): el status solo se revisaba en el login, y
     * los tokens Sanctum no vencen (sanctum.expiration = null) — un usuario
     * inactivado seguía usando la API con su token viejo indefinidamente.
     * Responde 401 (no 403) a propósito: el frontend (apiFetch) trata el 401
     * cerrando la sesión y mandando al login, que es lo correcto aquí.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->status !== 'ACTIVO') {

            $user->currentAccessToken()?->delete();

            return response()->json([
                'message' => 'Su usuario está inactivo. Contacte al administrador del sistema.'
            ], 401);
        }

        return $next($request);
    }
}
