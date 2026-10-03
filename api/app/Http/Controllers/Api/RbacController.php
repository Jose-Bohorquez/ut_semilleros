<?php #archivo: backend/app/Http/Controllers/Api/RbacController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\RolePermission;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\Rbac\PermissionResolver;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Panel de administración del RBAC granular (solo ADMIN_SISTEMA, ver
 * api/routes/api.php). Permite:
 *  - Consultar el catálogo de módulos/acciones disponibles.
 *  - Ver y editar qué permisos tiene cada rol (role_permissions).
 *  - Ver y editar excepciones por persona (user_permissions: grant/revoke).
 */
class RbacController extends Controller
{
    private const ROLES = ['ADMIN_SISTEMA', 'ADMINISTRATIVO', 'LIDER_SEMILLERO', 'ESTUDIANTE'];

    public function __construct(private PermissionResolver $resolver) {}

    /**
     * Catálogo completo: módulos, acciones y su id de permiso. El orden de
     * módulos y de acciones dentro de cada uno sigue config/rbac.php (no
     * orden alfabético): así "Ver" siempre aparece antes que "Crear/Editar"
     * en el panel, en vez del orden que le dé la BD.
     */
    public function catalog()
    {
        $byModuleAction = Permission::get()->keyBy(fn ($p) => "{$p->module}.{$p->action}");

        $modules = [];
        foreach (config('rbac.modules') as $moduleKey => $def) {
            $modules[$moduleKey]['label'] = $def['label'];
            foreach ($def['actions'] as $action => $label) {
                $p = $byModuleAction["{$moduleKey}.{$action}"] ?? null;
                if (!$p) {
                    continue; // catálogo y BD desincronizados (falta correr el seeder de nuevo)
                }
                $modules[$moduleKey]['actions'][] = ['id' => $p->id, 'action' => $p->action, 'label' => $p->label];
            }
        }

        return response()->json(['modules' => $modules, 'roles' => self::ROLES]);
    }

    /** Matriz rol × permiso (solo roles editables: no incluye ADMIN_SISTEMA, que siempre es total). */
    public function rolePermissions()
    {
        $editableRoles = array_values(array_diff(self::ROLES, ['ADMIN_SISTEMA']));

        $rows = RolePermission::whereIn('role', $editableRoles)->get(['role', 'permission_id']);
        $matrix = [];
        foreach ($rows as $r) {
            $matrix[$r->role][] = $r->permission_id;
        }
        foreach ($editableRoles as $role) {
            $matrix[$role] = $matrix[$role] ?? [];
        }

        return response()->json(['roles' => $editableRoles, 'matrix' => $matrix]);
    }

    /** Reemplaza el conjunto completo de permisos de un rol. */
    public function updateRolePermissions(Request $request, string $role)
    {
        if ($role === 'ADMIN_SISTEMA') {
            return response()->json(['message' => 'ADMIN_SISTEMA siempre tiene acceso total; no se puede editar.'], 422);
        }
        if (!in_array($role, self::ROLES, true)) {
            return response()->json(['message' => 'Rol inválido'], 422);
        }

        $data = $request->validate([
            'permission_ids'   => 'present|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        RolePermission::where('role', $role)->delete();
        foreach (array_unique($data['permission_ids']) as $permissionId) {
            RolePermission::create(['role' => $role, 'permission_id' => $permissionId]);
        }

        $this->forgetCacheForRole($role);

        return response()->json(['message' => 'Permisos del rol actualizados', 'role' => $role]);
    }

    /** Permisos efectivos de una persona (rol + overrides) y sus excepciones explícitas. */
    public function userPermissions(User $user)
    {
        $effective = $this->resolver->effective($user);
        $overrides = UserPermission::where('user_id', $user->id)
            ->with('permission')
            ->get()
            ->map(fn ($o) => [
                'permission_id' => $o->permission_id,
                'key'           => $o->permission->key(),
                'effect'        => $o->effect,
            ]);

        return response()->json([
            'user'      => ['id' => $user->id, 'name' => $user->name, 'email' => $user->email, 'role' => $user->role],
            'effective' => array_keys(array_filter($effective)),
            'overrides' => $overrides,
        ]);
    }

    /** Reemplaza las excepciones de una persona (lista de {permission_id, effect}). */
    public function updateUserPermissions(Request $request, User $user)
    {
        $data = $request->validate([
            'overrides'                 => 'present|array',
            'overrides.*.permission_id' => 'integer|exists:permissions,id',
            'overrides.*.effect'        => ['required', Rule::in(['grant', 'revoke'])],
        ]);

        UserPermission::where('user_id', $user->id)->delete();
        foreach ($data['overrides'] as $o) {
            UserPermission::create([
                'user_id'       => $user->id,
                'permission_id' => $o['permission_id'],
                'effect'        => $o['effect'],
                'granted_by'    => $request->user()->id,
            ]);
        }

        $this->resolver->forgetCache($user);

        return response()->json(['message' => 'Excepciones de la persona actualizadas', 'user_id' => $user->id]);
    }

    /** Listado liviano de personas, para el buscador del panel (sistema chico: sin paginar). */
    public function usersLite()
    {
        return response()->json([
            'users' => User::orderBy('name')->get(['id', 'name', 'email', 'role', 'status']),
        ]);
    }

    /* ───────────────────── Grupos de permisos (v2, 2026-09-29) ───────────────────── */

    /** Lista de grupos con su cantidad de integrantes y de permisos, para la vista general. */
    public function groups()
    {
        $groups = PermissionGroup::withCount(['users', 'permissions'])->orderBy('name')->get();
        return response()->json(['groups' => $groups]);
    }

    public function storeGroup(Request $request)
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:permission_groups,name',
            'description' => 'nullable|string|max:255',
        ]);

        $group = PermissionGroup::create($data);

        return response()->json(['message' => 'Grupo creado', 'group' => $group], 201);
    }

    public function updateGroup(Request $request, PermissionGroup $group)
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:100', Rule::unique('permission_groups', 'name')->ignore($group->id)],
            'description' => 'nullable|string|max:255',
        ]);

        $group->update($data);

        return response()->json(['message' => 'Grupo actualizado', 'group' => $group]);
    }

    public function destroyGroup(PermissionGroup $group)
    {
        $members = $group->users()->get(['users.id']);
        $group->delete();
        $members->each(fn (User $u) => $this->resolver->forgetCache($u));

        return response()->json(['message' => 'Grupo eliminado']);
    }

    /** Detalle de un grupo: integrantes (con nombre/rol) y los ids de permiso que otorga. */
    public function showGroup(PermissionGroup $group)
    {
        return response()->json([
            'group'         => $group,
            'member_ids'    => $group->users()->pluck('users.id'),
            'members'       => $group->users()->get(['users.id', 'users.name', 'users.email', 'users.role']),
            'permission_ids' => $group->permissions()->pluck('permissions.id'),
        ]);
    }

    /** Reemplaza los integrantes del grupo (personas de cualquier rol). */
    public function updateGroupMembers(Request $request, PermissionGroup $group)
    {
        $data = $request->validate([
            'user_ids'   => 'present|array',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        $before = $group->users()->pluck('users.id');
        $group->users()->sync($data['user_ids']);
        AuditTrail::pivot($group, 'users', $before->all(), $data['user_ids']);   // CU29

        $before->merge($data['user_ids'])->unique()->each(
            fn ($id) => $this->resolver->forgetCache(User::find($id))
        );

        return response()->json(['message' => 'Integrantes del grupo actualizados']);
    }

    /** Reemplaza los permisos que otorga el grupo a todos sus integrantes. */
    public function updateGroupPermissions(Request $request, PermissionGroup $group)
    {
        $data = $request->validate([
            'permission_ids'   => 'present|array',
            'permission_ids.*' => 'integer|exists:permissions,id',
        ]);

        $beforePermissions = $group->permissions()->pluck('permissions.id')->all();
        $group->permissions()->sync($data['permission_ids']);
        AuditTrail::pivot($group, 'permissions', $beforePermissions, $data['permission_ids']);   // CU29
        $this->resolver->forgetCacheForGroup($group);

        return response()->json(['message' => 'Permisos del grupo actualizados']);
    }

    private function forgetCacheForRole(string $role): void
    {
        User::where('role', $role)->get(['id'])->each(
            fn (User $u) => $this->resolver->forgetCache($u)
        );
    }
}
