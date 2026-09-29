<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\RolePermission;
use Illuminate\Database\Seeder;

/**
 * Crea el catálogo de permisos (config/rbac.php → tabla `permissions`) y los
 * permisos por defecto de cada rol (tabla `role_permissions`), idempotente:
 * se puede correr varias veces sin duplicar filas ni borrar overrides por
 * persona (`user_permissions` no la toca este seeder).
 */
class RbacSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = config('rbac.modules');

        $permissionIds = []; // "modulo.accion" => id
        foreach ($catalog as $module => $def) {
            foreach ($def['actions'] as $action => $label) {
                $permission = Permission::updateOrCreate(
                    ['module' => $module, 'action' => $action],
                    ['label' => $label]
                );
                $permissionIds["{$module}.{$action}"] = $permission->id;
            }
        }

        foreach (config('rbac.role_defaults') as $role => $modules) {
            if ($modules === ['*']) {
                continue; // ADMIN_SISTEMA: acceso total hardcodeado en el resolver, sin filas
            }
            foreach ($modules as $module => $actions) {
                foreach ($actions as $action) {
                    $key = "{$module}.{$action}";
                    if (!isset($permissionIds[$key])) {
                        continue; // acción no declarada en catálogo, se ignora en vez de romper el seed
                    }
                    RolePermission::firstOrCreate([
                        'role' => $role,
                        'permission_id' => $permissionIds[$key],
                    ]);
                }
            }
        }
    }
}
