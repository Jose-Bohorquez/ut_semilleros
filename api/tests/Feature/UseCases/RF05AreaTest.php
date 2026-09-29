<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\Area;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\Proposal;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF05 – Gestión de áreas de conocimiento. RN08: código único. RN01: sin
 * eliminación. Criterio: todo semillero y toda propuesta tiene al menos un área.
 */
class RF05AreaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
    }

    private function program(): Program
    {
        $f = Faculty::create(['code' => 'F', 'name' => 'F', 'status' => 'ACTIVO']);
        return Program::create(['code' => 'P', 'name' => 'P', 'type' => 'PREGRADO', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
    }

    public function test_create_requires_code_and_name_and_registers_active(): void
    {
        $this->postJson('/api/areas', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'code']);
        $this->postJson('/api/areas', ['code' => 'tic ', 'name' => 'Tecnologías de la información'])
            ->assertCreated()->assertJsonPath('area.code', 'TIC')->assertJsonPath('area.status', 'ACTIVO');
    }

    public function test_code_is_unique_case_insensitive(): void
    {
        $this->postJson('/api/areas', ['code' => 'TIC', 'name' => 'A'])->assertCreated();
        $this->postJson('/api/areas', ['code' => 'tic', 'name' => 'B'])
            ->assertStatus(422)->assertJsonValidationErrors(['code' => 'Ya existe un área con ese código.']);
    }

    public function test_areas_cannot_be_deleted_and_changes_are_audited(): void
    {
        $id = $this->postJson('/api/areas', ['code' => 'AUD', 'name' => 'Auditada'])->json('area.id');
        $this->deleteJson("/api/areas/{$id}")->assertStatus(405);
        $this->assertDatabaseHas('areas', ['id' => $id]);
        $this->assertDatabaseHas('audits', ['table_name' => 'areas', 'record_id' => $id]);
    }

    public function test_read_roles_and_write_only_admin(): void
    {
        $this->postJson('/api/areas', ['code' => 'X', 'name' => 'X'])->assertCreated();
        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->getJson('/api/areas')->assertOk();
            $this->postJson('/api/areas', ['code' => 'Y-' . $role, 'name' => 'Y'])->assertStatus(403);
        }
    }

    /* El estudiante no puede escribir, pero sí necesita leerlas para elegir
       una al crear su propuesta (bug real: antes daba 403). */
    public function test_estudiante_can_read_areas_but_not_write(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $this->getJson('/api/areas')->assertOk();
        $this->postJson('/api/areas', ['code' => 'Z', 'name' => 'Z'])->assertStatus(403);
    }

    /* Criterio: todo semillero tiene al menos un área */
    public function test_seedbed_requires_an_active_area(): void
    {
        $p = $this->program();
        $this->postJson('/api/seedbeds', ['name' => 'S', 'program_id' => $p->id, 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['area_id']);

        $off = Area::create(['code' => 'OFF', 'name' => 'Off', 'status' => 'INACTIVO']);
        $this->postJson('/api/seedbeds', ['name' => 'S', 'program_id' => $p->id, 'area_id' => $off->id, 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['area_id']);

        $area = Area::create(['code' => 'ON', 'name' => 'On', 'status' => 'ACTIVO']);
        $this->postJson('/api/seedbeds', ['name' => 'S', 'program_id' => $p->id, 'area_id' => $area->id, 'status' => 'ACTIVO'])
            ->assertCreated();
    }

    /* …pero un semillero existente conserva su área aunque se inactive */
    public function test_seedbed_keeps_its_area_after_it_is_inactivated(): void
    {
        $p = $this->program();
        $area = Area::create(['code' => 'A', 'name' => 'A', 'status' => 'ACTIVO']);
        $s = Seedbed::create(['name' => 'S', 'program_id' => $p->id, 'area_id' => $area->id, 'status' => 'ACTIVO']);
        $area->update(['status' => 'INACTIVO']);
        $this->putJson("/api/seedbeds/{$s->id}", ['name' => 'S2', 'program_id' => $p->id, 'area_id' => $area->id, 'status' => 'ACTIVO'])
            ->assertOk();
    }

    /* Criterio: toda propuesta tiene al menos un área */
    /* POST /proposals no admite ADMIN_SISTEMA (regla existente): se actúa
       como LIDER_SEMILLERO para probar la validación de area_id. */
    public function test_proposal_requires_an_active_area(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->postJson('/api/proposals', ['user_id' => $u->id, 'title' => 'T', 'description' => 'D', 'status' => 'PENDIENTE'])
            ->assertStatus(422)->assertJsonValidationErrors(['area_id']);

        $area = Area::create(['code' => 'A', 'name' => 'A', 'status' => 'ACTIVO']);
        $this->postJson('/api/proposals', ['user_id' => $u->id, 'area_id' => $area->id, 'title' => 'T', 'description' => 'D', 'status' => 'PENDIENTE'])
            ->assertCreated();
    }

    public function test_proposal_keeps_its_area_after_it_is_inactivated(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE']);
        $area = Area::create(['code' => 'A', 'name' => 'A', 'status' => 'ACTIVO']);
        $p = Proposal::create(['user_id' => $u->id, 'area_id' => $area->id, 'title' => 'T', 'description' => 'D', 'status' => 'PENDIENTE']);
        $area->update(['status' => 'INACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->putJson("/api/proposals/{$p->id}", ['user_id' => $u->id, 'area_id' => $area->id, 'title' => 'T2', 'description' => 'D2', 'status' => 'PENDIENTE'])
            ->assertOk();
    }
}
