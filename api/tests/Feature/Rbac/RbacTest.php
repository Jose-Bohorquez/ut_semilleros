<?php

namespace Tests\Feature\Rbac;

use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\User;
use App\Models\UserPermission;
use App\Services\Rbac\PermissionResolver;
use Database\Seeders\RbacSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * RBAC granular (2026-09-29): permisos por módulo+acción, asignables a un
 * rol completo (role_permissions) o a una persona como excepción
 * (user_permissions: grant añade, revoke quita, sobre lo que diga el rol).
 */
class RbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RbacSeeder::class);
    }

    public function test_admin_sistema_always_has_full_access_even_for_unknown_permission(): void
    {
        $resolver = app(PermissionResolver::class);
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);

        $this->assertTrue($resolver->can($admin, 'sia', 'curate'));
        $this->assertTrue($resolver->can($admin, 'modulo_que_no_existe', 'accion_x'));
    }

    public function test_role_default_grants_seeded_from_config(): void
    {
        $resolver = app(PermissionResolver::class);
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);

        $this->assertTrue($resolver->can($lider, 'seedbeds', 'view'));
        $this->assertTrue($resolver->can($lider, 'seedbeds', 'manage_members'));
        $this->assertFalse($resolver->can($lider, 'audits', 'view')); // solo ADMIN_SISTEMA
    }

    public function test_user_override_grant_adds_permission_role_does_not_have(): void
    {
        $resolver = app(PermissionResolver::class);
        $estudiante = User::factory()->create(['role' => 'ESTUDIANTE']);

        $this->assertFalse($resolver->can($estudiante, 'sia', 'curate'));

        $perm = Permission::where('module', 'sia')->where('action', 'curate')->firstOrFail();
        UserPermission::create(['user_id' => $estudiante->id, 'permission_id' => $perm->id, 'effect' => 'grant']);
        $resolver->forgetCache($estudiante);

        $this->assertTrue($resolver->can($estudiante, 'sia', 'curate'));
    }

    public function test_user_override_revoke_removes_permission_role_has_by_default(): void
    {
        $resolver = app(PermissionResolver::class);
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);

        $this->assertTrue($resolver->can($lider, 'seedbeds', 'view'));

        $perm = Permission::where('module', 'seedbeds')->where('action', 'view')->firstOrFail();
        UserPermission::create(['user_id' => $lider->id, 'permission_id' => $perm->id, 'effect' => 'revoke']);
        $resolver->forgetCache($lider);

        $this->assertFalse($resolver->can($lider, 'seedbeds', 'view'));
    }

    /* ───── Panel de administración RBAC (solo ADMIN_SISTEMA) ───── */

    public function test_only_admin_sistema_can_reach_rbac_panel(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->getJson('/api/rbac/catalog')->assertStatus(403);
    }

    public function test_catalog_lists_modules_and_actions(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->getJson('/api/rbac/catalog')
            ->assertOk()
            ->assertJsonPath('modules.sia.label', 'SIA (asistente)');
    }

    public function test_admin_can_replace_role_permissions(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $perm = Permission::where('module', 'projects')->where('action', 'view')->firstOrFail();
        $otherPerm = Permission::where('module', 'requests')->where('action', 'create')->firstOrFail();

        $this->putJson('/api/rbac/roles/ESTUDIANTE', ['permission_ids' => [$perm->id]])
            ->assertOk();

        $this->assertDatabaseHas('role_permissions', ['role' => 'ESTUDIANTE', 'permission_id' => $perm->id]);
        $this->assertDatabaseMissing('role_permissions', ['role' => 'ESTUDIANTE', 'permission_id' => $otherPerm->id]);

        $estudiante = User::factory()->create(['role' => 'ESTUDIANTE']);
        $this->assertTrue(app(PermissionResolver::class)->can($estudiante, 'projects', 'view'));
    }

    public function test_cannot_edit_admin_sistema_permissions(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->putJson('/api/rbac/roles/ADMIN_SISTEMA', ['permission_ids' => []])
            ->assertStatus(422);
    }

    public function test_admin_can_set_and_read_user_overrides(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $target = User::factory()->create(['role' => 'ESTUDIANTE', 'name' => 'Profe Invitado']);
        $perm = Permission::where('module', 'sia')->where('action', 'curate')->firstOrFail();

        $this->putJson("/api/rbac/users/{$target->id}", [
            'overrides' => [['permission_id' => $perm->id, 'effect' => 'grant']],
        ])->assertOk();

        $this->getJson("/api/rbac/users/{$target->id}")
            ->assertOk()
            ->assertJsonFragment(['effect' => 'grant'])
            ->assertJsonPath('user.name', 'Profe Invitado');

        $this->assertTrue(app(PermissionResolver::class)->can($target->fresh(), 'sia', 'curate'));
    }

    public function test_role_permissions_endpoint_never_includes_admin_sistema(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/rbac/roles')->assertOk();
        $this->assertNotContains('ADMIN_SISTEMA', $response->json('roles'));
    }

    /* ───── Grupos de permisos (v2) ───── */

    public function test_group_grant_adds_permission_across_different_roles(): void
    {
        $resolver = app(PermissionResolver::class);
        $estudiante = User::factory()->create(['role' => 'ESTUDIANTE']);
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $perm = Permission::where('module', 'sia')->where('action', 'curate')->firstOrFail();

        $group = PermissionGroup::create(['name' => 'Comité editorial']);
        $group->users()->attach([$estudiante->id, $lider->id]);
        $group->permissions()->attach($perm->id);
        $resolver->forgetCacheForGroup($group);

        $this->assertTrue($resolver->can($estudiante, 'sia', 'curate'));
        $this->assertTrue($resolver->can($lider, 'sia', 'curate'));

        $outsider = User::factory()->create(['role' => 'ESTUDIANTE']);
        $this->assertFalse($resolver->can($outsider, 'sia', 'curate'));
    }

    public function test_user_revoke_overrides_group_grant(): void
    {
        $resolver = app(PermissionResolver::class);
        $estudiante = User::factory()->create(['role' => 'ESTUDIANTE']);
        $perm = Permission::where('module', 'sia')->where('action', 'curate')->firstOrFail();

        $group = PermissionGroup::create(['name' => 'Comité editorial 2']);
        $group->users()->attach($estudiante->id);
        $group->permissions()->attach($perm->id);
        $resolver->forgetCache($estudiante);
        $this->assertTrue($resolver->can($estudiante, 'sia', 'curate'));

        UserPermission::create(['user_id' => $estudiante->id, 'permission_id' => $perm->id, 'effect' => 'revoke']);
        $resolver->forgetCache($estudiante);
        $this->assertFalse($resolver->can($estudiante, 'sia', 'curate'));
    }

    public function test_admin_can_manage_group_lifecycle_via_api(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $estudiante = User::factory()->create(['role' => 'ESTUDIANTE', 'name' => 'Estudiante Grupo']);
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'name' => 'Lider Grupo']);
        $perm = Permission::where('module', 'sia')->where('action', 'curate')->firstOrFail();

        $created = $this->postJson('/api/rbac/groups', ['name' => 'Comunicados', 'description' => 'Para avisos institucionales'])
            ->assertCreated()->json('group');

        $this->putJson("/api/rbac/groups/{$created['id']}/members", ['user_ids' => [$estudiante->id, $lider->id]])->assertOk();
        $this->putJson("/api/rbac/groups/{$created['id']}/permissions", ['permission_ids' => [$perm->id]])->assertOk();

        $this->getJson("/api/rbac/groups/{$created['id']}")
            ->assertOk()
            ->assertJsonFragment(['name' => 'Estudiante Grupo'])
            ->assertJsonFragment(['name' => 'Lider Grupo']);

        $this->assertTrue(app(PermissionResolver::class)->can($estudiante->fresh(), 'sia', 'curate'));

        $this->getJson('/api/rbac/groups')->assertOk()->assertJsonFragment(['name' => 'Comunicados']);

        $this->deleteJson("/api/rbac/groups/{$created['id']}")->assertOk();
        $this->assertFalse(app(PermissionResolver::class)->can($estudiante->fresh(), 'sia', 'curate'));
    }

    public function test_group_name_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        PermissionGroup::create(['name' => 'Duplicado']);
        $this->postJson('/api/rbac/groups', ['name' => 'Duplicado'])->assertStatus(422);
    }

    public function test_only_admin_sistema_can_manage_groups(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->getJson('/api/rbac/groups')->assertStatus(403);
    }
}
