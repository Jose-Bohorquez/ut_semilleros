<?php

namespace Tests\Feature\Request;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\MembershipRequest;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF10 — Gestión de Solicitudes de ingreso (CU10)
 */
class RequestCrudTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        return Seedbed::create(['name' => 'Semillero Test', 'program_id' => $program->id, 'status' => 'ACTIVO']);
    }

    public function test_unauthenticated_cannot_access_requests(): void
    {
        $response = $this->getJson('/api/requests');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_requests(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/requests');
        $response->assertStatus(200)->assertJsonStructure(['requests']);
    }

    public function test_authenticated_user_can_create_request(): void
    {
        $actingUser = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $dataUser   = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($actingUser);
        $seedbed  = $this->seedbed();
        $response = $this->postJson('/api/requests', [
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
            'status'     => 'PENDIENTE',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('requests', [
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
        ]);
    }

    public function test_request_create_requires_user_seedbed_and_status(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/requests', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_id', 'seedbed_id', 'status']);
    }

    public function test_request_status_must_be_valid_value(): void
    {
        $actingUser = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $dataUser   = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($actingUser);
        $seedbed  = $this->seedbed();
        $response = $this->postJson('/api/requests', [
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
            'status'     => 'INVALIDO',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_request(): void
    {
        $actingUser = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $dataUser   = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($actingUser);
        $seedbed  = $this->seedbed();
        $req      = MembershipRequest::create([
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
            'status'     => 'PENDIENTE',
        ]);
        $response = $this->putJson("/api/requests/{$req->id}", [
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
            'status'     => 'APROBADA',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'APROBADA']);
    }

    public function test_request_update_returns_404_for_missing(): void
    {
        $actingUser = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $dataUser   = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($actingUser);
        $seedbed  = $this->seedbed();
        $response = $this->putJson('/api/requests/9999', [
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
            'status'     => 'APROBADA',
        ]);
        $response->assertStatus(404);
    }
}
