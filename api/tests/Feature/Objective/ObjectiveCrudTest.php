<?php

namespace Tests\Feature\Objective;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\Objective;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF08 — Gestión de Objetivos (CU08)
 */
class ObjectiveCrudTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        return Seedbed::create(['name' => 'Semillero Test', 'program_id' => $program->id, 'status' => 'ACTIVO']);
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
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
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
            'content'    => 'Contenido',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_objective(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed   = $this->seedbed();
        $objective = Objective::create(['seedbed_id' => $seedbed->id, 'content' => 'Original', 'status' => 'ACTIVO']);
        $response  = $this->putJson("/api/objectives/{$objective->id}", [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Actualizado',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('objectives', ['id' => $objective->id, 'content' => 'Actualizado']);
    }

    public function test_objective_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $response = $this->putJson('/api/objectives/9999', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'X',
        ]);
        $response->assertStatus(404);
    }

    public function test_objective_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $objective = Objective::create(['seedbed_id' => $this->seedbed()->id, 'content' => 'Test', 'status' => 'ACTIVO']);
        $response  = $this->putJson("/api/objectives/{$objective->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('objectives', ['id' => $objective->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('objectives', ['id' => $objective->id]);
    }
}
