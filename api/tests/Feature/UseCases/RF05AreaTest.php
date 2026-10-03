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
        $this->postJson('/api/seedbeds', ['name' => 'S', 'programs' => [$p->id], 'areas' => [], 'objetivo_general' => 'Objetivo general de prueba', 'authorization_reference' => 'Of 1', 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['areas']);

        $off = Area::create(['code' => 'OFF', 'name' => 'Off', 'status' => 'INACTIVO']);
        $this->postJson('/api/seedbeds', ['name' => 'S', 'programs' => [$p->id], 'areas' => [$off->id], 'objetivo_general' => 'Objetivo general de prueba', 'authorization_reference' => 'Of 1', 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['areas.0']);

        $area = Area::create(['code' => 'ON', 'name' => 'On', 'status' => 'ACTIVO']);
        $this->postJson('/api/seedbeds', ['code' => 'SB-' . uniqid(), 'name' => 'S', 'programs' => [$p->id], 'areas' => [$area->id], 'objetivo_general' => 'Objetivo general de prueba', 'authorization_reference' => 'Of 1', 'status' => 'ACTIVO'])
            ->assertCreated();
    }

    /* …pero un semillero existente conserva su área aunque se inactive */
    public function test_seedbed_keeps_its_area_after_it_is_inactivated(): void
    {
        $p = $this->program();
        $area = Area::create(['code' => 'A', 'name' => 'A', 'status' => 'ACTIVO']);
        $s = Seedbed::create(['code' => 'SB-' . uniqid(), 'name' => 'S', 'objetivo_general' => 'Objetivo general de prueba', 'authorization_reference' => 'Of 1', 'status' => 'ACTIVO']);
        $s->programs()->attach($p->id);
        $s->areas()->attach($area->id);
        $area->update(['status' => 'INACTIVO']);
        $this->putJson("/api/seedbeds/{$s->id}", ['code' => $s->code, 'name' => 'S2', 'programs' => [$p->id], 'areas' => [$area->id], 'objetivo_general' => 'Objetivo general de prueba', 'authorization_reference' => 'Of 1', 'status' => 'ACTIVO'])
            ->assertOk();
    }

    /* Criterio: toda propuesta tiene al menos un área */
    /* CU25: solo el ESTUDIANTE registra propuestas (el personal ya no puede, CU25-H4). */
    public function test_proposal_requires_an_active_area(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE']);
        $faculty = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'P', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        Sanctum::actingAs($u);
        $this->postJson('/api/proposals', ['user_id' => $u->id, 'program_id' => $program->id, 'title' => 'T', 'description' => 'Descripción con longitud suficiente.', 'status' => 'PENDIENTE'])
            ->assertStatus(422)->assertJsonValidationErrors(['areas']);

        $area = Area::create(['code' => 'A', 'name' => 'A', 'status' => 'ACTIVO']);
        $this->postJson('/api/proposals', ['user_id' => $u->id, 'program_id' => $program->id, 'areas' => [$area->id], 'title' => 'T', 'description' => 'Descripción con longitud suficiente.', 'status' => 'PENDIENTE'])
            ->assertCreated();
    }

    public function test_proposal_keeps_its_area_after_it_is_inactivated(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE']);
        $faculty = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'P', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $area = Area::create(['code' => 'A', 'name' => 'A', 'status' => 'ACTIVO']);
        $p = Proposal::create(['user_id' => $u->id, 'program_id' => $program->id, 'title' => 'T', 'description' => 'Descripción con longitud suficiente.', 'status' => 'PENDIENTE']);
        $p->areas()->attach($area->id);
        $area->update(['status' => 'INACTIVO']);
        Sanctum::actingAs($u);
        $this->putJson("/api/proposals/{$p->id}", ['user_id' => $u->id, 'program_id' => $program->id, 'areas' => [$area->id], 'title' => 'T2', 'description' => 'Descripción actualizada con longitud suficiente.', 'status' => 'PENDIENTE'])
            ->assertOk();
    }
}
