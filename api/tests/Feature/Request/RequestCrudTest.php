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
        $seedbed = Seedbed::create(['name' => 'Semillero Test', 'status' => 'ACTIVO']);
        $seedbed->programs()->attach($program->id);
        return $seedbed;
    }

    /** Payload base válido de CU22 (programa, teléfono, mensaje). */
    private function payload(Seedbed $seedbed, array $overrides = []): array
    {
        return array_merge([
            'program_id' => $seedbed->programs()->first()->id,
            'phone'      => '3001234567',
            'message'    => 'Mensaje de prueba con longitud suficiente para la validación.',
        ], $overrides);
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
        $response = $this->postJson('/api/requests', array_merge($this->payload($seedbed), [
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
            'status'     => 'PENDIENTE',
        ]));
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
        $response = $this->postJson('/api/requests', array_merge($this->payload($seedbed), [
            'user_id'    => $dataUser->id,
            'seedbed_id' => $seedbed->id,
            'status'     => 'INVALIDO',
        ]));
        $response->assertStatus(422);
    }

    /* CU24: el PUT genérico /requests/{id} se retiró (permitía saltarse E1-E3);
       la resolución va solo por update-status — ver RequestManagementTest. */
    public function test_generic_request_update_route_no_longer_exists(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->putJson('/api/requests/1', ['status' => 'APROBADA'])->assertStatus(405);
    }

    /* ───── CU22: programa, teléfono, mensaje ───── */

    public function test_create_requires_program_phone_and_message(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $seedbed = $this->seedbed();
        $this->postJson('/api/requests', [
            'seedbed_id' => $seedbed->id, 'user_id' => 1, 'status' => 'PENDIENTE',
        ])->assertStatus(422)->assertJsonValidationErrors(['program_id', 'phone', 'message']);
    }

    public function test_phone_must_be_7_to_15_digits(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $seedbed = $this->seedbed();
        $this->postJson('/api/requests', array_merge($this->payload($seedbed, ['phone' => '123']), [
            'seedbed_id' => $seedbed->id, 'user_id' => 1, 'status' => 'PENDIENTE',
        ]))->assertStatus(422)->assertJsonValidationErrors(['phone']);
    }

    public function test_message_requires_minimum_length(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $seedbed = $this->seedbed();
        $this->postJson('/api/requests', array_merge($this->payload($seedbed, ['message' => 'corto']), [
            'seedbed_id' => $seedbed->id, 'user_id' => 1, 'status' => 'PENDIENTE',
        ]))->assertStatus(422)->assertJsonValidationErrors(['message']);
    }

    public function test_program_must_belong_to_the_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $seedbed = $this->seedbed();
        $faculty = Faculty::create(['name' => 'Otra facultad', 'status' => 'ACTIVO']);
        $otherProgram = Program::create(['name' => 'Otro programa', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);

        $this->postJson('/api/requests', array_merge($this->payload($seedbed, ['program_id' => $otherProgram->id]), [
            'seedbed_id' => $seedbed->id, 'user_id' => 1, 'status' => 'PENDIENTE',
        ]))->assertStatus(422)->assertJsonValidationErrors(['program_id']);
    }

    /* E4: el semillero inactivo no recibe solicitudes */
    public function test_cannot_request_inactive_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $seedbed = $this->seedbed();
        $seedbed->update(['status' => 'INACTIVO']);

        $this->postJson('/api/requests', array_merge($this->payload($seedbed), [
            'seedbed_id' => $seedbed->id, 'user_id' => 1, 'status' => 'PENDIENTE',
        ]))->assertStatus(409);
    }
}
