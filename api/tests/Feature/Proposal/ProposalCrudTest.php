<?php

namespace Tests\Feature\Proposal;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proposal;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF11 — Gestión de Propuestas de investigación (CU11)
 */
class ProposalCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_proposals(): void
    {
        $response = $this->getJson('/api/proposals');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_proposals(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/proposals');
        $response->assertStatus(200)->assertJsonStructure(['proposals']);
    }

    public function test_authenticated_user_can_create_proposal(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $user     = User::factory()->create(['role' => 'ESTUDIANTE']);
        $response = $this->postJson('/api/proposals', [
            'user_id'     => $user->id,
            'title'       => 'Investigación sobre IA',
            'description' => 'Propuesta para aplicar IA en educación',
            'status'      => 'PENDIENTE',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('proposals', ['title' => 'Investigación sobre IA']);
    }

    public function test_proposal_create_requires_all_fields(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/proposals', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_id', 'title', 'description', 'status']);
    }

    public function test_proposal_status_must_be_valid_value(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $user     = User::factory()->create(['role' => 'ESTUDIANTE']);
        $response = $this->postJson('/api/proposals', [
            'user_id'     => $user->id,
            'title'       => 'Test',
            'description' => 'Descripción',
            'status'      => 'INVALIDO',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_proposal(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $user     = User::factory()->create(['role' => 'ESTUDIANTE']);
        $proposal = Proposal::create([
            'user_id'     => $user->id,
            'title'       => 'Original',
            'description' => 'Descripción original',
            'status'      => 'PENDIENTE',
        ]);
        $response = $this->putJson("/api/proposals/{$proposal->id}", [
            'user_id'     => $user->id,
            'title'       => 'Actualizada',
            'description' => 'Nueva descripción',
            'status'      => 'APROBADA',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'status' => 'APROBADA']);
    }

    public function test_proposal_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $user     = User::factory()->create(['role' => 'ESTUDIANTE']);
        $response = $this->putJson('/api/proposals/9999', [
            'user_id'     => $user->id,
            'title'       => 'X',
            'description' => 'Y',
            'status'      => 'PENDIENTE',
        ]);
        $response->assertStatus(404);
    }

    public function test_proposal_create_rejects_nonexistent_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/proposals', [
            'user_id'     => 9999,
            'title'       => 'Test',
            'description' => 'Descripción',
            'status'      => 'PENDIENTE',
        ]);
        $response->assertStatus(422);
    }
}
