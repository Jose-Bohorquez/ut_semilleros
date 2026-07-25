<?php

namespace Tests\Feature\Faculty;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF02 — Gestión de Facultades (CU02)
 */
class FacultyCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_faculties(): void
    {
        $response = $this->getJson('/api/faculties');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_faculties(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/faculties');
        $response->assertStatus(200)->assertJsonStructure(['faculties']);
    }

    public function test_authenticated_user_can_create_faculty(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/faculties', [
            'name'   => 'Facultad de Ingeniería',
            'status' => 'ACTIVO',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('faculties', ['name' => 'Facultad de Ingeniería']);
    }

    public function test_faculty_create_requires_name(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/faculties', ['status' => 'ACTIVO']);
        $response->assertStatus(422);
    }

    public function test_faculty_create_requires_status(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/faculties', ['name' => 'Sin estado']);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_faculty(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $faculty = Faculty::create(['name' => 'Original', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/faculties/{$faculty->id}", [
            'name'   => 'Actualizada',
            'status' => 'ACTIVO',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('faculties', ['id' => $faculty->id, 'name' => 'Actualizada']);
    }

    public function test_faculty_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->putJson('/api/faculties/9999', ['name' => 'X', 'status' => 'ACTIVO']);
        $response->assertStatus(404);
    }

    public function test_faculty_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $faculty = Faculty::create(['name' => 'Test', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/faculties/{$faculty->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('faculties', ['id' => $faculty->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('faculties', ['id' => $faculty->id]);
    }

    public function test_faculty_toggle_status_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->putJson('/api/faculties/9999/toggle-status');
        $response->assertStatus(404);
    }
}
