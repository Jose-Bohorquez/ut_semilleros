<?php

namespace App\Http\Middleware;

use App\Services\Rbac\PermissionResolver;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * RBAC granular: ->middleware('permission:modulo,accion')
 *
 * Nuevo, en paralelo al `role:` existente (RoleMiddleware) mientras se migra
 * ruta por ruta — ver App\Services\Rbac\PermissionResolver.
 */
class PermissionMiddleware
{
    public function __construct(private PermissionResolver $resolver) {}

    public function handle(Request $request, Closure $next, string $module, string $action): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        if (!$this->resolver->can($user, $module, $action)) {
            return response()->json(['message' => 'No autorizado para realizar esta acción'], 403);
        }

        return $next($request);
    }
}
