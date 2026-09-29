<?php

namespace App\Services\Rbac;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Cache;

/**
 * RBAC granular (permisos por módulo + acción, por rol y por persona).
 *
 * Resolución de `can($user, 'modulo', 'accion')`:
 *   1. ADMIN_SISTEMA siempre true — acceso total, no se le puede quitar
 *      (decisión de Jose, 2026-09-29: "yo como admin tendré full access a todo").
 *   2. Si hay una excepción por persona (user_permissions) para ese permiso,
 *      manda ella: 'grant' → true aunque el rol no lo tenga, 'revoke' → false
 *      aunque el rol sí lo tenga.
 *   3. Si no hay excepción, manda lo que tenga asignado el rol (role_permissions).
 *
 * No reemplaza al `role:` de las rutas todavía (conviven a propósito mientras
 * se prueba el sistema nuevo, CU por CU) — ver CLAUDE.md sección RBAC.
 */
class PermissionResolver
{
    /** @return array<string,bool> mapa "modulo.accion" => true, de TODOS los permisos efectivos del usuario */
    public function effective(User $user): array
    {
        if ($user->role === 'ADMIN_SISTEMA') {
            return collect(Permission::all())->mapWithKeys(fn ($p) => [$p->key() => true])->all();
        }

        return Cache::remember("rbac:effective:{$user->id}", 60, function () use ($user) {
            $rolePerms = Permission::query()
                ->join('role_permissions', 'role_permissions.permission_id', '=', 'permissions.id')
                ->where('role_permissions.role', $user->role)
                ->get(['permissions.module', 'permissions.action', 'permissions.id']);

            $effective = [];
            foreach ($rolePerms as $p) {
                $effective[$p->id] = true;
            }

            $overrides = UserPermission::where('user_id', $user->id)->get(['permission_id', 'effect']);
            foreach ($overrides as $o) {
                $effective[$o->permission_id] = $o->effect === 'grant';
            }

            $byId = Permission::whereIn('id', array_keys($effective))->get()->keyBy('id');
            $result = [];
            foreach ($effective as $permId => $allowed) {
                if ($allowed && isset($byId[$permId])) {
                    $result[$byId[$permId]->key()] = true;
                }
            }
            return $result;
        });
    }

    public function can(User $user, string $module, string $action): bool
    {
        if ($user->role === 'ADMIN_SISTEMA') {
            return true;
        }
        return (bool) ($this->effective($user)["{$module}.{$action}"] ?? false);
    }

    public function forgetCache(User $user): void
    {
        Cache::forget("rbac:effective:{$user->id}");
    }
}
