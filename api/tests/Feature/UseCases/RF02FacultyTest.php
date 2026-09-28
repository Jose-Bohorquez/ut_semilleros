<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF02 – Gestión de facultades (RN08: código único; sin eliminación;
 * una facultad inactiva no aparece en los formularios de programas ni semilleros).
 */
class RF02FacultyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
    }

    public function test_create_requires_code_and_name_and_registers_active(): void
    {
        $this->postJson('/api/faculties', [])->assertStatus(422)->assertJsonValidationErrors(['code', 'name']);
        $this->postJson('/api/faculties', ['code' => ' fce ', 'name' => 'Ciencias Económicas'])
            ->assertCreated()->assertJsonPath('faculty.code', 'FCE')->assertJsonPath('faculty.status', 'ACTIVO');
    }

    /* Criterio: no se registran dos facultades con el mismo código (sin distinguir mayúsculas) */
    public function test_code_is_unique(): void
    {
        $this->postJson('/api/faculties', ['code' => 'IDEAD', 'name' => 'A'])->assertCreated();
        $this->postJson('/api/faculties', ['code' => 'idead', 'name' => 'B'])
            ->assertStatus(422)->assertJsonValidationErrors(['code' => 'Ya existe una facultad con ese código.']);
    }

    public function test_update_keeps_own_code_and_rejects_duplicates(): void
    {
        $a = Faculty::create(['code' => 'A1', 'name' => 'A', 'status' => 'ACTIVO']);
        Faculty::create(['code' => 'B1', 'name' => 'B', 'status' => 'ACTIVO']);
        $this->putJson("/api/faculties/{$a->id}", ['code' => 'A1', 'name' => 'A editada', 'status' => 'ACTIVO'])->assertOk();
        $this->putJson("/api/faculties/{$a->id}", ['code' => 'B1', 'name' => 'A', 'status' => 'ACTIVO'])->assertStatus(422);
        $this->putJson("/api/faculties/{$a->id}", ['code' => 'A1', 'name' => 'A', 'status' => 'BORRADO'])->assertStatus(422);
    }

    /* Facultades anteriores a RF02 (sin código): al editarlas se exige */
    public function test_legacy_faculty_without_code_must_get_one_on_edit(): void
    {
        $f = Faculty::create(['name' => 'Antigua', 'status' => 'ACTIVO']);
        $this->putJson("/api/faculties/{$f->id}", ['name' => 'Antigua', 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['code']);
    }

    public function test_faculties_cannot_be_deleted(): void
    {
        $f = Faculty::create(['code' => 'X', 'name' => 'X', 'status' => 'ACTIVO']);
        $this->deleteJson("/api/faculties/{$f->id}")->assertStatus(405);
        $this->assertDatabaseHas('faculties', ['id' => $f->id]);
    }

    /* Criterio: una facultad inactiva no se puede usar en programas */
    public function test_inactive_faculty_cannot_be_chosen_for_a_program(): void
    {
        $off = Faculty::create(['code' => 'OFF', 'name' => 'Off', 'status' => 'INACTIVO']);
        $this->postJson('/api/programs', ['name' => 'P', 'faculty_id' => $off->id, 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['faculty_id']);
    }

    /* …pero un programa existente puede conservar su facultad aunque se haya inactivado */
    public function test_program_keeps_its_faculty_after_it_is_inactivated(): void
    {
        $f = Faculty::create(['code' => 'F', 'name' => 'F', 'status' => 'ACTIVO']);
        $p = Program::create(['name' => 'P', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
        $f->update(['status' => 'INACTIVO']);
        $this->putJson("/api/programs/{$p->id}", ['code' => 'P2', 'type' => 'PREGRADO', 'name' => 'P2', 'faculty_id' => $f->id, 'status' => 'ACTIVO'])->assertOk();
    }

    /* …ni en semilleros (a través del programa) */
    public function test_seedbed_cannot_use_program_of_inactive_faculty(): void
    {
        $f = Faculty::create(['code' => 'F', 'name' => 'F', 'status' => 'INACTIVO']);
        $p = Program::create(['name' => 'P', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
        $this->postJson('/api/seedbeds', ['name' => 'S', 'program_id' => $p->id, 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['program_id']);
    }

    public function test_seedbed_keeps_its_program_on_edit(): void
    {
        $f = Faculty::create(['code' => 'F', 'name' => 'F', 'status' => 'ACTIVO']);
        $p = Program::create(['name' => 'P', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
        $s = Seedbed::create(['name' => 'S', 'program_id' => $p->id, 'status' => 'ACTIVO']);
        $f->update(['status' => 'INACTIVO']);
        $this->putJson("/api/seedbeds/{$s->id}", ['name' => 'S2', 'program_id' => $p->id, 'status' => 'ACTIVO'])->assertOk();
    }

    /* RN07: altas y cambios quedan en la auditoría (AuditObserver) */
    public function test_changes_are_audited(): void
    {
        $id = $this->postJson('/api/faculties', ['code' => 'AUD', 'name' => 'Aud'])->json('faculty.id');
        $this->assertDatabaseHas('audits', ['table_name' => 'faculties', 'record_id' => $id]);
    }
}
