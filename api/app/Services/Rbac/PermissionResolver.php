<?php

namespace App\Services\Rbac;

use App\Models\Permission;
use App\Models\User;
use App\Models\UserPermission;
use Illuminate\Support\Facades\Cache;

/**
 * RBAC granular (permisos por módulo + acción, por rol, por grupo y por persona).
 *
 * Resolución de `can($user, 'modulo', 'accion')`:
 *   1. ADMIN_SISTEMA siempre true — acceso total, no se le puede quitar
 *      (decisión de Jose, 2026-09-29: "yo como admin tendré full access a todo").
 *   2. Si hay una excepción por persona (user_permissions) para ese permiso,
 *      manda ella y nada más importa: 'grant' → true, 'revoke' → false. Es la
 *      capa más específica, así que gana incluso sobre un grupo que lo otorgue.
 *   3. Si no hay excepción, el permiso está activo si lo da el ROL o algún
 *      GRUPO al que pertenezca (un grupo solo suma permisos, nunca quita —
 *      para quitar algo puntual se usa la excepción por persona del punto 2).
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
                ->pluck('permissions.id');

            $groupPerms = Permission::query()
                ->join('permission_group_permissions', 'permission_group_permissions.permission_id', '=', 'permissions.id')
                ->join('permission_group_user', 'permission_group_user.group_id', '=', 'permission_group_permissions.group_id')
                ->where('permission_group_user.user_id', $user->id)
                ->pluck('permissions.id');

            $effective = [];
            foreach ($rolePerms->merge($groupPerms) as $permId) {
                $effective[$permId] = true;
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

    public function forgetCacheForGroup(\App\Models\PermissionGroup $group): void
    {
        $group->users()->get(['users.id'])->each(fn (User $u) => $this->forgetCache($u));
    }
}
