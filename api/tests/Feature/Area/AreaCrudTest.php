<?php

namespace Tests\Feature\Area;

use Tests\TestCase;
use App\Models\User;
use App\Models\Area;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF05 — Gestión de Áreas (CU05)
 */
class AreaCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_areas(): void
    {
        $response = $this->getJson('/api/areas');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_areas(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/areas');
        $response->assertStatus(200)->assertJsonStructure(['areas']);
    }

    public function test_authenticated_user_can_create_area(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/areas', [
            'name' => 'Área de Sistemas',
            'code' => 'SIS-01',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('areas', ['code' => 'SIS-01']);
    }

    public function test_area_create_requires_name_and_code(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/areas', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_area_code_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        Area::create(['name' => 'Primera', 'code' => 'A-001', 'status' => 'ACTIVO']);
        $response = $this->postJson('/api/areas', ['name' => 'Segunda', 'code' => 'A-001']);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_area(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $area     = Area::create(['name' => 'Original', 'code' => 'A-OR', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/areas/{$area->id}", [
            'name' => 'Actualizada',
            'code' => 'A-OR',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('areas', ['id' => $area->id, 'name' => 'Actualizada']);
    }

    public function test_area_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->putJson('/api/areas/9999', ['name' => 'X', 'code' => 'Y']);
        $response->assertStatus(404);
    }

    public function test_area_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $area     = Area::create(['name' => 'Test', 'code' => 'A-TST', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/areas/{$area->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('areas', ['id' => $area->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('areas', ['id' => $area->id]);
    }
}
