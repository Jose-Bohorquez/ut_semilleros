<?php

namespace Tests\Feature\Result;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\Result;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF09 — Gestión de Resultados (CU09)
 */
class ResultCrudTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        return Seedbed::create(['name' => 'Semillero Test', 'program_id' => $program->id, 'status' => 'ACTIVO']);
    }

    public function test_unauthenticated_cannot_access_results(): void
    {
        $response = $this->getJson('/api/results');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_results(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/results');
        $response->assertStatus(200)->assertJsonStructure(['results']);
    }

    public function test_authenticated_user_can_create_result(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $response = $this->postJson('/api/results', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Publicación de artículo científico',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('results', ['seedbed_id' => $seedbed->id]);
    }

    public function test_result_create_requires_seedbed_and_content(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/results', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['seedbed_id', 'content']);
    }

    public function test_result_create_rejects_nonexistent_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/results', [
            'seedbed_id' => 9999,
            'content'    => 'Contenido',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_result(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $result   = Result::create(['seedbed_id' => $seedbed->id, 'content' => 'Original', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/results/{$result->id}", [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Actualizado',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('results', ['id' => $result->id, 'content' => 'Actualizado']);
    }

    public function test_result_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $response = $this->putJson('/api/results/9999', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'X',
        ]);
        $response->assertStatus(404);
    }

    public function test_result_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $result   = Result::create(['seedbed_id' => $this->seedbed()->id, 'content' => 'Test', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/results/{$result->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('results', ['id' => $result->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('results', ['id' => $result->id]);
    }
}
