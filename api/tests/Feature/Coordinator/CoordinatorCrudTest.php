<?php

namespace Tests\Feature\Coordinator;

use Tests\TestCase;
use App\Models\User;
use App\Models\Coordinator;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF07 — Gestión de Coordinadores (CU07)
 */
class CoordinatorCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_coordinators(): void
    {
        $response = $this->getJson('/api/coordinators');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_coordinators(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/coordinators');
        $response->assertStatus(200)->assertJsonStructure(['coordinators']);
    }

    public function test_authenticated_user_can_create_coordinator(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/coordinators', [
            'name'   => 'Carlos Pérez',
            'email'  => 'carlos@test.com',
            'status' => 'ACTIVO',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('coordinators', ['email' => 'carlos@test.com']);
    }

    public function test_coordinator_create_requires_name_and_email(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/coordinators', ['status' => 'ACTIVO']);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_coordinator_email_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        Coordinator::create(['name' => 'Primero', 'email' => 'dup@test.com', 'status' => 'ACTIVO']);
        $response = $this->postJson('/api/coordinators', [
            'name'   => 'Segundo',
            'email'  => 'dup@test.com',
            'status' => 'ACTIVO',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_coordinator(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $coord    = Coordinator::create(['name' => 'Original', 'email' => 'orig@test.com', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/coordinators/{$coord->id}", [
            'name'   => 'Actualizado',
            'email'  => 'orig@test.com',
            'status' => 'ACTIVO',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('coordinators', ['id' => $coord->id, 'name' => 'Actualizado']);
    }

    public function test_coordinator_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->putJson('/api/coordinators/9999', [
            'name'   => 'X',
            'email'  => 'x@x.com',
            'status' => 'ACTIVO',
        ]);
        $response->assertStatus(404);
    }

    public function test_coordinator_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $coord    = Coordinator::create(['name' => 'Test', 'email' => 'test@test.com', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/coordinators/{$coord->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('coordinators', ['id' => $coord->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('coordinators', ['id' => $coord->id]);
    }

    /* ───── CU12-A5: solo ADMIN_SISTEMA escribe ───── */

    public function test_lider_cannot_create_coordinator(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/coordinators', ['name' => 'X', 'email' => 'x@x.com', 'status' => 'ACTIVO']);
        $response->assertStatus(403);
    }

    public function test_lider_can_read_coordinators(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->getJson('/api/coordinators')->assertStatus(200);
    }

    public function test_administrativo_cannot_update_coordinator(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $coord = Coordinator::create(['name' => 'Test', 'email' => 'adm@test.com', 'status' => 'ACTIVO']);
        $this->putJson("/api/coordinators/{$coord->id}", ['name' => 'Y', 'email' => 'adm@test.com', 'status' => 'ACTIVO'])
            ->assertStatus(403);
    }

    /* ───── documento único (RN08) ───── */

    public function test_document_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        Coordinator::create(['name' => 'Primero', 'document' => '123456', 'email' => 'a@test.com', 'status' => 'ACTIVO']);
        $this->postJson('/api/coordinators', ['name' => 'Segundo', 'document' => '123456', 'email' => 'b@test.com', 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['document']);
    }

    public function test_can_create_coordinator_without_document(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->postJson('/api/coordinators', ['name' => 'Sin Documento', 'email' => 'sd@test.com', 'status' => 'ACTIVO'])
            ->assertCreated();
    }

    /* CU12-A1 */
    public function test_show_returns_detail(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $coord = Coordinator::create(['name' => 'Test', 'document' => '999', 'email' => 'det@test.com', 'status' => 'ACTIVO']);

        $this->getJson("/api/coordinators/{$coord->id}")
            ->assertOk()
            ->assertJsonPath('coordinator.document', '999')
            ->assertJsonStructure(['coordinator' => ['created_at', 'updated_at']]);
    }
}
