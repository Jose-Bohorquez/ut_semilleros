<?php #archivo : backend/app/http/middleware/rolemiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Maneja autorización por roles.
     *
     * Uso:
     * ->middleware('role:ADMIN_SISTEMA')
     */
    public function handle(
        Request $request,
        Closure $next,
        ...$roles
    ): Response {

        $user = $request->user();

        /*
        |--------------------------------------------------------------------------
        | Usuario no autenticado
        |--------------------------------------------------------------------------
        */

        if (!$user) {

            return response()->json([
                'message' => 'No autenticado'
            ], 401);

        }

        /*
        |--------------------------------------------------------------------------
        | Usuario sin permisos
        |--------------------------------------------------------------------------
        */

        if (!in_array($user->role, $roles)) {

            return response()->json([
                'message' => 'No autorizado para realizar esta acción'
            ], 403);

        }

        return $next($request);
    }
}