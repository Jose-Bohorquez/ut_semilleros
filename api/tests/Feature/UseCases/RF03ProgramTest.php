<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF03 – Gestión de programas: facultad, código (único, RN08), nombre y tipo
 * (Pregrado / Posgrado). Sin eliminación (RN01). Auditoría (RN07).
 */
class RF03ProgramTest extends TestCase
{
    use RefreshDatabase;

    private Faculty $fac;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->fac = Faculty::create(['code' => 'FIT', 'name' => 'Ingeniería', 'status' => 'ACTIVO']);
    }

    private function payload(array $o = []): array
    {
        return array_merge(['faculty_id' => $this->fac->id, 'code' => 'isis ', 'name' => 'Ingeniería de Sistemas', 'type' => 'pregrado'], $o);
    }

    public function test_entries_are_required_and_program_registers_active(): void
    {
        $this->postJson('/api/programs', [])->assertStatus(422)->assertJsonValidationErrors(['faculty_id', 'code', 'name', 'type']);
        $this->postJson('/api/programs', $this->payload())->assertCreated()
            ->assertJsonPath('program.code', 'ISIS')->assertJsonPath('program.type', 'PREGRADO')->assertJsonPath('program.status', 'ACTIVO');
    }

    /* Criterio: el tipo solo admite Pregrado o Posgrado */
    public function test_type_only_accepts_pregrado_or_posgrado(): void
    {
        $this->postJson('/api/programs', $this->payload(['type' => 'DOCTORADO']))
            ->assertStatus(422)->assertJsonValidationErrors(['type' => 'El tipo solo admite Pregrado o Posgrado.']);
        $this->postJson('/api/programs', $this->payload(['code' => 'MAE', 'type' => 'Posgrado']))->assertCreated();
    }

    /* RN08 */
    public function test_code_is_unique(): void
    {
        $this->postJson('/api/programs', $this->payload())->assertCreated();
        $this->postJson('/api/programs', $this->payload(['code' => 'ISIS', 'name' => 'Otro']))
            ->assertStatus(422)->assertJsonValidationErrors(['code' => 'Ya existe un programa con ese código.']);
    }

    /* Criterio: todo programa pertenece a una facultad (activa al crear) */
    public function test_program_belongs_to_an_active_faculty(): void
    {
        $this->postJson('/api/programs', $this->payload(['faculty_id' => null]))->assertStatus(422)->assertJsonValidationErrors(['faculty_id']);
        $off = Faculty::create(['code' => 'OFF', 'name' => 'Off', 'status' => 'INACTIVO']);
        $this->postJson('/api/programs', $this->payload(['faculty_id' => $off->id]))->assertStatus(422)->assertJsonValidationErrors(['faculty_id']);
    }

    public function test_legacy_program_without_code_or_type_must_get_them_on_edit(): void
    {
        $p = Program::create(['name' => 'Antiguo', 'faculty_id' => $this->fac->id, 'status' => 'ACTIVO']);
        $this->putJson("/api/programs/{$p->id}", ['name' => 'Antiguo', 'faculty_id' => $this->fac->id, 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['code', 'type']);
        $this->putJson("/api/programs/{$p->id}", $this->payload(['status' => 'ACTIVO']))->assertOk();
    }

    public function test_programs_cannot_be_deleted_and_changes_are_audited(): void
    {
        $id = $this->postJson('/api/programs', $this->payload())->json('program.id');
        $this->deleteJson("/api/programs/{$id}")->assertStatus(405);
        $this->assertDatabaseHas('programs', ['id' => $id]);
        $this->assertDatabaseHas('audits', ['table_name' => 'programs', 'record_id' => $id]);
    }

    /* Consulta: Líder y Administrativo leen; solo el Administrador escribe */
    public function test_read_roles_and_write_only_admin(): void
    {
        $this->postJson('/api/programs', $this->payload())->assertCreated();
        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->getJson('/api/programs')->assertOk()->assertJsonStructure(['programs' => [['code', 'type', 'faculty']]]);
            $this->postJson('/api/programs', $this->payload())->assertStatus(403);
        }
    }
}
