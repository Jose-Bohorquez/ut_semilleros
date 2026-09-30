<?php

namespace Tests\Feature\SeedbedMember;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\SeedbedMember;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF12 — Gestión de Integrantes de Semillero (CU21)
 */
class SeedbedMemberTest extends TestCase
{
    use RefreshDatabase;

    private function programActivo(): Program
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        return Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
    }

    private function seedbed(): Seedbed
    {
        return Seedbed::create(['name' => 'Semillero Test', 'status' => 'ACTIVO']);
    }

    private function seedbedWithLeader(): array
    {
        $seedbed = $this->seedbed();
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $seedbed->users()->attach($lider->id, ['role' => 'LIDER']);
        return [$seedbed, $lider];
    }

    private function memberPayload(int $programId, string $code = 'EST-001'): array
    {
        return [
            'name'         => 'Integrante de prueba',
            'student_code' => $code,
            'program_id'   => $programId,
            'level'        => 'PR',
            'email'        => 'integrante@ut.edu.co',
            'address'      => 'Calle 1 # 2-3',
            'phone'        => '3001234567',
        ];
    }

    public function test_unauthenticated_cannot_list_members(): void
    {
        $seedbed  = $this->seedbed();
        $response = $this->getJson("/api/seedbeds/{$seedbed->id}/members");
        $response->assertStatus(401);
    }

    public function test_leader_can_list_members_of_own_seedbed(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->getJson("/api/seedbeds/{$seedbed->id}/members");
        $response->assertStatus(200)->assertJsonStructure(['members']);
    }

    public function test_administrativo_can_list_but_not_write(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $this->getJson("/api/seedbeds/{$seedbed->id}/members")->assertStatus(200);

        $program = $this->programActivo();
        $this->postJson("/api/seedbeds/{$seedbed->id}/members", $this->memberPayload($program->id))
            ->assertStatus(403);
    }

    public function test_admin_can_add_member_to_any_seedbed(): void
    {
        $seedbed = $this->seedbed();
        $program = $this->programActivo();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson("/api/seedbeds/{$seedbed->id}/members", $this->memberPayload($program->id));
        $response->assertStatus(201);
        $this->assertDatabaseHas('seedbed_members', [
            'seedbed_id'   => $seedbed->id,
            'student_code' => 'EST-001',
        ]);
    }

    public function test_add_member_requires_core_fields(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson("/api/seedbeds/{$seedbed->id}/members", []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'student_code', 'program_id', 'level', 'email']);
    }

    public function test_add_member_rejects_invalid_level(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        $program = $this->programActivo();
        Sanctum::actingAs($lider);
        $payload = $this->memberPayload($program->id);
        $payload['level'] = 'DOCTORADO';
        $this->postJson("/api/seedbeds/{$seedbed->id}/members", $payload)
            ->assertStatus(422)->assertJsonValidationErrors(['level']);
    }

    public function test_add_member_rejects_inactive_program(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        $faculty  = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $inactive = Program::create(['name' => 'P inactivo', 'faculty_id' => $faculty->id, 'status' => 'INACTIVO']);
        Sanctum::actingAs($lider);
        $this->postJson("/api/seedbeds/{$seedbed->id}/members", $this->memberPayload($inactive->id))
            ->assertStatus(422)->assertJsonValidationErrors(['program_id']);
    }

    /* E1: código ya registrado como integrante ACTIVO del mismo semillero */
    public function test_add_member_rejects_duplicate_active_code_in_same_seedbed(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        $program = $this->programActivo();
        Sanctum::actingAs($lider);
        $this->postJson("/api/seedbeds/{$seedbed->id}/members", $this->memberPayload($program->id))
            ->assertStatus(201);
        $this->postJson("/api/seedbeds/{$seedbed->id}/members", $this->memberPayload($program->id))
            ->assertStatus(422)->assertJsonValidationErrors(['student_code']);
    }

    public function test_same_code_allowed_in_different_seedbeds(): void
    {
        [$seedbedA, $liderA] = $this->seedbedWithLeader();
        $seedbedB = $this->seedbed();
        $seedbedB->users()->attach($liderA->id, ['role' => 'LIDER']);
        $program = $this->programActivo();
        Sanctum::actingAs($liderA);
        $this->postJson("/api/seedbeds/{$seedbedA->id}/members", $this->memberPayload($program->id))
            ->assertStatus(201);
        $this->postJson("/api/seedbeds/{$seedbedB->id}/members", $this->memberPayload($program->id))
            ->assertStatus(201);
    }

    public function test_authenticated_user_can_update_member(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        $program = $this->programActivo();
        Sanctum::actingAs($lider);
        $member = SeedbedMember::create(array_merge($this->memberPayload($program->id), [
            'seedbed_id' => $seedbed->id, 'status' => 'ACTIVO',
        ]));
        $payload = $this->memberPayload($program->id);
        $payload['name'] = 'Nombre actualizado';
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}/members/{$member->id}", $payload);
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbed_members', ['id' => $member->id, 'name' => 'Nombre actualizado']);
    }

    public function test_toggle_status_requires_reason_to_inactivate(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        $program = $this->programActivo();
        Sanctum::actingAs($lider);
        $member = SeedbedMember::create(array_merge($this->memberPayload($program->id), [
            'seedbed_id' => $seedbed->id, 'status' => 'ACTIVO',
        ]));
        $this->putJson("/api/seedbeds/{$seedbed->id}/members/{$member->id}/toggle-status", [])
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);
    }

    public function test_toggle_status_inactivates_with_reason(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        $program = $this->programActivo();
        Sanctum::actingAs($lider);
        $member = SeedbedMember::create(array_merge($this->memberPayload($program->id), [
            'seedbed_id' => $seedbed->id, 'status' => 'ACTIVO',
        ]));
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}/members/{$member->id}/toggle-status", [
            'reason' => 'Se graduó del programa',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbed_members', ['id' => $member->id, 'status' => 'INACTIVO']);
    }

    /* ───── RN06 (CU21 E3): el líder solo gestiona integrantes de sus semilleros ───── */

    public function test_leader_cannot_add_member_to_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->seedbed();
        $program = $this->programActivo();
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->postJson("/api/seedbeds/{$seedbed->id}/members", $this->memberPayload($program->id))
            ->assertStatus(403);
    }

    public function test_leader_cannot_list_members_of_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->getJson("/api/seedbeds/{$seedbed->id}/members")->assertStatus(403);
    }

    public function test_leader_cannot_update_member_of_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->seedbed();
        $program = $this->programActivo();
        $member = SeedbedMember::create(array_merge($this->memberPayload($program->id), [
            'seedbed_id' => $seedbed->id, 'status' => 'ACTIVO',
        ]));
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->putJson("/api/seedbeds/{$seedbed->id}/members/{$member->id}", $this->memberPayload($program->id))
            ->assertStatus(403);
    }

    public function test_leader_cannot_toggle_status_of_member_they_do_not_own(): void
    {
        $seedbed = $this->seedbed();
        $program = $this->programActivo();
        $member = SeedbedMember::create(array_merge($this->memberPayload($program->id), [
            'seedbed_id' => $seedbed->id, 'status' => 'ACTIVO',
        ]));
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->putJson("/api/seedbeds/{$seedbed->id}/members/{$member->id}/toggle-status", ['reason' => 'motivo válido'])
            ->assertStatus(403);
    }

    public function test_list_members_returns_404_for_missing_seedbed_when_admin(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/seedbeds/9999/members');
        $response->assertStatus(404);
    }
}
