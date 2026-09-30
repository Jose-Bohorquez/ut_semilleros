<?php

namespace Tests\Feature\Objective;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Area;
use App\Models\Seedbed;
use App\Models\Objective;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF08 — Gestión de Objetivos (CU19)
 */
class ObjectiveCrudTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $area = Area::create(['name' => 'Área Test', 'code' => 'AT-' . uniqid(), 'status' => 'ACTIVO']);
        $seedbed = Seedbed::create(['name' => 'Semillero Test', 'status' => 'ACTIVO']);
        $seedbed->programs()->attach($program->id);
        $seedbed->areas()->attach($area->id);
        return $seedbed;
    }

    /** Semillero + líder ya asignado como responsable (pivot). */
    private function seedbedWithLeader(): array
    {
        $seedbed = $this->seedbed();
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $seedbed->users()->attach($lider->id, ['role' => 'LIDER']);
        return [$seedbed, $lider];
    }

    public function test_unauthenticated_cannot_access_objectives(): void
    {
        $response = $this->getJson('/api/objectives');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_objectives(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/objectives');
        $response->assertStatus(200)->assertJsonStructure(['objectives']);
    }

    public function test_authenticated_user_can_create_objective(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/objectives', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Desarrollar competencias investigativas',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('objectives', ['seedbed_id' => $seedbed->id]);
    }

    public function test_objective_create_requires_seedbed_and_content(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/objectives', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['seedbed_id', 'content']);
    }

    public function test_objective_create_rejects_nonexistent_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/objectives', [
            'seedbed_id' => 9999,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ]);
        $response->assertStatus(422);
    }

    /* CU19 paso 6: contenido entre 10 y 2000 caracteres */
    public function test_objective_content_requires_minimum_length(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/objectives', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'corto',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['content']);
    }

    public function test_objective_content_rejects_over_max_length(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/objectives', [
            'seedbed_id' => $seedbed->id,
            'content'    => str_repeat('a', 2001),
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['content']);
    }

    public function test_authenticated_user_can_update_objective(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $objective = Objective::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido original de prueba', 'status' => 'ACTIVO']);
        $response  = $this->putJson("/api/objectives/{$objective->id}", [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido actualizado de prueba',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('objectives', ['id' => $objective->id, 'content' => 'Contenido actualizado de prueba']);
    }

    public function test_objective_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $response = $this->putJson('/api/objectives/9999', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ]);
        $response->assertStatus(404);
    }

    public function test_objective_status_can_be_toggled_without_deletion(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $objective = Objective::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido de prueba', 'status' => 'ACTIVO']);
        $response  = $this->putJson("/api/objectives/{$objective->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('objectives', ['id' => $objective->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('objectives', ['id' => $objective->id]);
    }

    /* ───── RN06 (CU19 E2): el líder solo gestiona objetivos de sus semilleros ───── */

    public function test_leader_cannot_create_objective_for_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->postJson('/api/objectives', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ])->assertStatus(403);
    }

    public function test_leader_cannot_update_objective_of_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->seedbed();
        $objective = Objective::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido original', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->putJson("/api/objectives/{$objective->id}", [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido modificado sin permiso',
        ])->assertStatus(403);
    }

    public function test_leader_cannot_toggle_status_of_objective_they_do_not_own(): void
    {
        $seedbed = $this->seedbed();
        $objective = Objective::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido de prueba', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->putJson("/api/objectives/{$objective->id}/toggle-status")->assertStatus(403);
    }

    public function test_leader_cannot_delete_objective_they_do_not_own(): void
    {
        $seedbed = $this->seedbed();
        $objective = Objective::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido de prueba', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->deleteJson("/api/objectives/{$objective->id}")->assertStatus(403);
    }

    public function test_admin_can_manage_any_seedbed_objective(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->postJson('/api/objectives', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido creado por el administrador',
        ])->assertStatus(201);
    }

    /* CU19 A3 / actor secundario: Administrativo es solo consulta (2026-09-30,
       mismo criterio que CU16 para semilleros). */
    public function test_administrativo_cannot_write_objectives(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $this->postJson('/api/objectives', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ])->assertStatus(403);
    }
}
