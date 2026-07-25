<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    /**
     * Determina a dónde redireccionar si no está autenticado.
     *
     * En APIs NO redireccionamos.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }

    /**
     * Manejo personalizado para APIs.
     */
    protected function unauthenticated($request, array $guards)
    {
        abort(response()->json([
            'message' => 'No autenticado'
        ], 401));
    }
}