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
 * RF13 — Gestión de Semilleros (CU13). Ronda B: programas y áreas son
 * selección múltiple (tablas pivote seedbed_program / seedbed_area), no un
 * solo program_id/area_id como en la Ronda A.
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

    /** Crea un semillero directo en BD (sin pasar por el endpoint), con su
     *  programa y área ya asignados en las tablas pivote. */
    private function makeSeedbed(array $overrides = []): Seedbed
    {
        $seedbed = Seedbed::create(array_merge([
            'code' => 'SB-' . uniqid(),
            'name' => 'Semillero',
            'objetivo_general' => 'x objetivo largo',
            'authorization_reference' => 'Of 1',
            'status' => 'ACTIVO',
        ], $overrides));
        $seedbed->programs()->attach($this->program()->id);
        $seedbed->areas()->attach($this->area()->id);
        return $seedbed;
    }

    /** Payload base válido para crear/editar (CU13 paso 7/8). */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'code'             => 'SB-' . uniqid(),
            'name'             => 'Semillero Test',
            'programs'         => [$this->program()->id],
            'areas'            => [$this->area()->id],
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

    /** CU13: crea con múltiples programas y múltiples áreas a la vez. */
    public function test_can_create_seedbed_with_multiple_programs_and_areas(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $p1 = $this->program();
        $p2 = $this->program();
        $a1 = $this->area();
        $a2 = $this->area();

        $response = $this->postJson('/api/seedbeds', $this->payload([
            'programs' => [$p1->id, $p2->id],
            'areas'    => [$a1->id, $a2->id],
        ]));
        $response->assertStatus(201);

        $seedbedId = $response->json('seedbed.id');
        $this->assertDatabaseHas('seedbed_program', ['seedbed_id' => $seedbedId, 'program_id' => $p1->id]);
        $this->assertDatabaseHas('seedbed_program', ['seedbed_id' => $seedbedId, 'program_id' => $p2->id]);
        $this->assertDatabaseHas('seedbed_area', ['seedbed_id' => $seedbedId, 'area_id' => $a1->id]);
        $this->assertDatabaseHas('seedbed_area', ['seedbed_id' => $seedbedId, 'area_id' => $a2->id]);
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

    public function test_seedbed_create_requires_at_least_one_program(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['programs' => []]));
        $response->assertStatus(422)->assertJsonValidationErrors(['programs']);
    }

    public function test_seedbed_create_requires_at_least_one_area(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['areas' => []]));
        $response->assertStatus(422)->assertJsonValidationErrors(['areas']);
    }

    public function test_seedbed_create_rejects_nonexistent_program(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', $this->payload(['programs' => [9999]]));
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
        $seedbed  = $this->makeSeedbed(['code' => 'SB-1', 'name' => 'Original']);
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload([
            'code' => 'SB-1', 'name' => 'Actualizado',
            'programs' => $seedbed->programs()->pluck('programs.id')->all(),
            'areas'    => $seedbed->areas()->pluck('areas.id')->all(),
        ]));
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'name' => 'Actualizado']);
    }

    /** Actualizar puede cambiar la lista completa de programas/áreas (sync). */
    public function test_update_replaces_programs_and_areas(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = $this->makeSeedbed(['code' => 'SB-SYNC']);
        $oldProgramId = $seedbed->programs()->first()->id;
        $newProgram = $this->program();
        $newArea = $this->area();

        $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload([
            'code' => 'SB-SYNC',
            'programs' => [$newProgram->id],
            'areas' => [$newArea->id],
        ]))->assertStatus(200);

        $this->assertDatabaseMissing('seedbed_program', ['seedbed_id' => $seedbed->id, 'program_id' => $oldProgramId]);
        $this->assertDatabaseHas('seedbed_program', ['seedbed_id' => $seedbed->id, 'program_id' => $newProgram->id]);
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
        $seedbed  = $this->makeSeedbed(['code' => 'SB-2']);
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status", ['reason' => 'Cierre temporal de prueba']);
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'status' => 'INACTIVO', 'inactivation_reason' => 'Cierre temporal de prueba']);
    }

    /* ───── CU15: cambiar estado (E2 motivo obligatorio, rechazo automático de solicitudes) ───── */

    public function test_inactivating_without_reason_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = $this->makeSeedbed(['code' => 'SB-CU15-1']);
        $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status")
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);
    }

    public function test_inactivating_rejects_pending_requests_automatically(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = $this->makeSeedbed(['code' => 'SB-CU15-2']);
        $estudiante = User::factory()->create(['role' => 'ESTUDIANTE']);
        $pending = \App\Models\MembershipRequest::create([
            'user_id' => $estudiante->id, 'seedbed_id' => $seedbed->id, 'status' => 'PENDIENTE',
        ]);

        $response = $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status", ['reason' => 'Cierre por vacaciones']);

        $response->assertStatus(200)->assertJsonPath('rejected_requests_count', 1);
        $this->assertDatabaseHas('requests', [
            'id' => $pending->id, 'status' => 'RECHAZADA', 'reason' => 'Semillero inactivo',
        ]);
    }

    public function test_activating_does_not_require_reason(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = $this->makeSeedbed(['code' => 'SB-CU15-3', 'status' => 'INACTIVO']);
        $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status")
            ->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'status' => 'ACTIVO']);
    }

    /* ───── RN06: el líder solo modifica los semilleros de los que es responsable ───── */

    public function test_leader_cannot_update_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->makeSeedbed(['code' => 'SB-3', 'name' => 'Ajeno']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload(['code' => 'SB-3']))
            ->assertStatus(403);
    }

    public function test_leader_can_update_seedbed_they_lead(): void
    {
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $seedbed = $this->makeSeedbed(['code' => 'SB-4', 'name' => 'Propio']);
        $seedbed->users()->attach($lider->id, ['role' => 'LIDER']);
        Sanctum::actingAs($lider);

        $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload([
            'code' => 'SB-4', 'name' => 'Actualizado',
            'programs' => $seedbed->programs()->pluck('programs.id')->all(),
            'areas'    => $seedbed->areas()->pluck('areas.id')->all(),
        ]))->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'name' => 'Actualizado']);
    }

    public function test_leader_cannot_toggle_status_of_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->makeSeedbed(['code' => 'SB-5', 'name' => 'Ajeno']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status")->assertStatus(403);
    }

    /* CU13-A1 / bug histórico C-13: GET /seedbeds/{id} existía como ruta pero
       el método no, daba 500. */
    public function test_show_returns_detail(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = $this->makeSeedbed(['code' => 'SB-6', 'name' => 'Detalle']);

        $this->getJson("/api/seedbeds/{$seedbed->id}")
            ->assertOk()
            ->assertJsonPath('seedbed.code', 'SB-6')
            ->assertJsonPath('seedbed.programs.0.id', $seedbed->programs()->first()->id);
    }

    /* ───── CU14 E3: edición concurrente ───── */

    public function test_update_rejects_when_modified_by_another_user_meanwhile(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = $this->makeSeedbed(['code' => 'SB-CONC']);
        $staleTimestamp = $seedbed->updated_at->copy()->subMinute()->toJSON();

        $response = $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload([
            'code' => 'SB-CONC',
            'programs' => $seedbed->programs()->pluck('programs.id')->all(),
            'areas' => $seedbed->areas()->pluck('areas.id')->all(),
            'expected_updated_at' => $staleTimestamp,
        ]));

        $response->assertStatus(409);
    }

    public function test_update_succeeds_when_timestamp_matches(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $seedbed = $this->makeSeedbed(['code' => 'SB-CONC2']);

        $response = $this->putJson("/api/seedbeds/{$seedbed->id}", $this->payload([
            'code' => 'SB-CONC2',
            'programs' => $seedbed->programs()->pluck('programs.id')->all(),
            'areas' => $seedbed->areas()->pluck('areas.id')->all(),
            'expected_updated_at' => $seedbed->updated_at->toJSON(),
        ]));

        $response->assertStatus(200);
    }
}
