<?php

namespace Tests\Feature\Group;

use Tests\TestCase;
use App\Models\User;
use App\Models\Group;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF06 — Gestión de Grupos (CU06)
 */
class GroupCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_groups(): void
    {
        $response = $this->getJson('/api/groups');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_groups(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/groups');
        $response->assertStatus(200)->assertJsonStructure(['groups']);
    }

    public function test_authenticated_user_can_create_group(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/groups', [
            'name' => 'Grupo Alpha',
            'code' => 'GRP-001',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('groups', ['code' => 'GRP-001']);
    }

    public function test_group_create_requires_name_and_code(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/groups', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_group_code_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        Group::create(['name' => 'Primero', 'code' => 'G-001', 'status' => 'ACTIVO']);
        $response = $this->postJson('/api/groups', ['name' => 'Segundo', 'code' => 'G-001']);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_group(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $group    = Group::create(['name' => 'Original', 'code' => 'G-OR', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/groups/{$group->id}", [
            'name' => 'Actualizado',
            'code' => 'G-OR',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('groups', ['id' => $group->id, 'name' => 'Actualizado']);
    }

    public function test_group_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->putJson('/api/groups/9999', ['name' => 'X', 'code' => 'Y']);
        $response->assertStatus(404);
    }

    public function test_group_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $group    = Group::create(['name' => 'Test', 'code' => 'G-TST', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/groups/{$group->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('groups', ['id' => $group->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('groups', ['id' => $group->id]);
    }
}
