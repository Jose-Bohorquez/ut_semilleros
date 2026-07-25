<?php

namespace Tests\Feature\SeedbedMember;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF12 — Gestión de Integrantes de Semillero (CU12)
 */
class SeedbedMemberTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        return Seedbed::create(['name' => 'Semillero Test', 'program_id' => $program->id, 'status' => 'ACTIVO']);
    }

    public function test_unauthenticated_cannot_list_members(): void
    {
        $seedbed  = $this->seedbed();
        $response = $this->getJson("/api/seedbeds/{$seedbed->id}/members");
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_members(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $response = $this->getJson("/api/seedbeds/{$seedbed->id}/members");
        $response->assertStatus(200)->assertJsonStructure(['members']);
    }

    public function test_authenticated_user_can_add_member(): void
    {
        $actor    = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        Sanctum::actingAs($actor);
        $seedbed  = $this->seedbed();
        $member   = User::factory()->create(['role' => 'ESTUDIANTE']);
        $response = $this->postJson("/api/seedbeds/{$seedbed->id}/members", [
            'user_id' => $member->id,
            'role'    => 'INVESTIGADOR',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('seedbed_user', [
            'seedbed_id' => $seedbed->id,
            'user_id'    => $member->id,
        ]);
    }

    public function test_add_member_requires_user_id_and_role(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $response = $this->postJson("/api/seedbeds/{$seedbed->id}/members", []);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_id', 'role']);
    }

    public function test_add_member_rejects_invalid_role(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $member   = User::factory()->create(['role' => 'ESTUDIANTE']);
        $response = $this->postJson("/api/seedbeds/{$seedbed->id}/members", [
            'user_id' => $member->id,
            'role'    => 'ROL_INVALIDO',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_remove_member(): void
    {
        $actor   = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        Sanctum::actingAs($actor);
        $seedbed = $this->seedbed();
        $member  = User::factory()->create(['role' => 'ESTUDIANTE']);
        $seedbed->users()->attach($member->id, ['role' => 'AUXILIAR']);
        $this->assertDatabaseHas('seedbed_user', ['seedbed_id' => $seedbed->id, 'user_id' => $member->id]);
        $response = $this->deleteJson("/api/seedbeds/{$seedbed->id}/members/{$member->id}");
        $response->assertStatus(200);
        $this->assertDatabaseMissing('seedbed_user', ['seedbed_id' => $seedbed->id, 'user_id' => $member->id]);
    }

    public function test_list_members_returns_404_for_missing_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/seedbeds/9999/members');
        $response->assertStatus(404);
    }
}
