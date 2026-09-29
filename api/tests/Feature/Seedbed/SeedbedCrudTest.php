<?php

namespace Tests\Feature\Seedbed;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Area;
use App\Models\Seedbed;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF13 — Gestión de Semilleros (CU13)
 */
class SeedbedCrudTest extends TestCase
{
    use RefreshDatabase;

    private function program(): Program
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        return Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
    }

    private function area(): Area
    {
        return Area::create(['name' => 'Área Test', 'code' => 'AT-' . uniqid(), 'status' => 'ACTIVO']);
    }

    /** Payload base válido para crear/editar (CU13 paso 7/8). */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code'             => 'SB-' . uniqid(),
            'name'             => 'Semillero Test',
            'program_id'       => $this->program()->id,
            'area_id'          => $this->area()->id,
            'objetivo_general' => 'Fomentar la investigación aplicada',
            'authorization_reference' => 'Oficio 001 de 2026',
            'status'           => 'ACTIVO',
        ], $overrides);
    }

    public function test_unauthenticated_cannot_access_seedbeds(): void
    {
        $response = $this->getJson('/api/seedbeds');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_seedbeds(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/seedbeds');
        $response->assertStatus(200)->assertJsonStructure(['seedbeds']);
    }

    public function test_authenticated_user_can_create_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['name' => 'Semillero Innovación']));
        $response->assertStatus(201);
        $this->assertDatabaseHas('seedbeds', ['name' => 'Semillero Innovación']);
    }

    /** CU13 paso 9: el líder que crea queda asignado como responsable. */
    public function test_creating_leader_is_assigned_as_responsible(): void
    {
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/seedbeds', $this->payload());
        $seedbedId = $response->json('seedbed.id');

        $this->assertDatabaseHas('seedbed_user', [
            'seedbed_id' => $seedbedId, 'user_id' => $lider->id, 'role' => 'LIDER',
        ]);
    }

    public function test_seedbed_create_requires_program_id(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['program_id' => null]));
        $response->assertStatus(422);
    }

    public function test_seedbed_create_rejects_nonexistent_program(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['program_id' => 9999]));
        $response->assertStatus(422);
    }

    public function test_objetivo_general_requires_minimum_length(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['objetivo_general' => 'corto']));
        $response->assertStatus(422)->assertJsonValidationErrors(['objetivo_general']);
    }

    public function test_authorization_reference_is_required(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['authorization_reference' => null]));
        $response->assertStatus(422)->assertJsonValidationErrors(['authorization_reference']);
    }

    public function test_code_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->postJson('/api/seedbeds', $this->payload(['code' => 'SB-DUP']))->assertCreated();
        $this->postJson('/api/seedbeds', $this->payload(['code' => 'SB-DUP']))
            ->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    /* Admin siempre puede editar/togglear cualquier semillero (RN06). */
    public function test_admin_can_update_any_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $program  = $this->program();
        $area     = $this->area();
        $seedbed  = Seedbed::create(['code' => 'SB-1', 'name' => 'Original', 'program_id' => $program->id, 'area_id' => $area->id, 'objetivo_general' => 'x objetivo largo', 'authorization_reference' => 'Of 1', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload(['code' => 'SB-1', 'name' => 'Actualizado', 'program_id' => $program->id, 'area_id' => $area->id]));
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'name' => 'Actualizado']);
    }

    public function test_seedbed_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->putJson('/api/seedbeds/9999', $this->payload());
        $response->assertStatus(404);
    }

    public function test_admin_can_toggle_any_seedbed_status(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed  = Seedbed::create(['code' => 'SB-2', 'name' => 'Test', 'program_id' => $this->program()->id, 'area_id' => $this->area()->id, 'objetivo_general' => 'x objetivo largo', 'authorization_reference' => 'Of 2', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id]);
    }

    /* ───── RN06: el líder solo modifica los semilleros de los que es responsable ───── */

    public function test_leader_cannot_update_seedbed_they_do_not_lead(): void
    {
        $seedbed = Seedbed::create(['code' => 'SB-3', 'name' => 'Ajeno', 'program_id' => $this->program()->id, 'area_id' => $this->area()->id, 'objetivo_general' => 'x objetivo largo', 'authorization_reference' => 'Of 3', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload(['code' => 'SB-3']))
            ->assertStatus(403);
    }

    public function test_leader_can_update_seedbed_they_lead(): void
    {
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $seedbed = Seedbed::create(['code' => 'SB-4', 'name' => 'Propio', 'program_id' => $this->program()->id, 'area_id' => $this->area()->id, 'objetivo_general' => 'x objetivo largo', 'authorization_reference' => 'Of 4', 'status' => 'ACTIVO']);
        $seedbed->users()->attach($lider->id, ['role' => 'LIDER']);
        Sanctum::actingAs($lider);

        $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload(['code' => 'SB-4', 'name' => 'Actualizado']))
            ->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'name' => 'Actualizado']);
    }

    public function test_leader_cannot_toggle_status_of_seedbed_they_do_not_lead(): void
    {
        $seedbed = Seedbed::create(['code' => 'SB-5', 'name' => 'Ajeno', 'program_id' => $this->program()->id, 'area_id' => $this->area()->id, 'objetivo_general' => 'x objetivo largo', 'authorization_reference' => 'Of 5', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status")->assertStatus(403);
    }

    /* CU13-A1 / bug histórico C-13: GET /seedbeds/{id} existía como ruta pero
       el método no, daba 500. */
    public function test_show_returns_detail(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = Seedbed::create(['code' => 'SB-6', 'name' => 'Detalle', 'program_id' => $this->program()->id, 'area_id' => $this->area()->id, 'objetivo_general' => 'x objetivo largo', 'authorization_reference' => 'Of 6', 'status' => 'ACTIVO']);

        $this->getJson("/api/seedbeds/{$seedbed->id}")
            ->assertOk()
            ->assertJsonPath('seedbed.code', 'SB-6')
            ->assertJsonPath('seedbed.program.id', $seedbed->program_id);
    }
}
