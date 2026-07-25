<?php

namespace Tests\Feature\Program;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF03 — Gestión de Programas (CU03)
 */
class ProgramCrudTest extends TestCase
{
    use RefreshDatabase;

    private function faculty(): Faculty
    {
        return Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
    }

    public function test_unauthenticated_cannot_access_programs(): void
    {
        $response = $this->getJson('/api/programs');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_programs(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/programs');
        $response->assertStatus(200)->assertJsonStructure(['programs']);
    }

    public function test_authenticated_user_can_create_program(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $faculty = $this->faculty();
        $response = $this->postJson('/api/programs', [
            'name'       => 'Ingeniería de Sistemas',
            'faculty_id' => $faculty->id,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('programs', ['name' => 'Ingeniería de Sistemas']);
    }

    public function test_program_create_requires_faculty_id(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/programs', [
            'name'   => 'Sin facultad',
            'status' => 'ACTIVO',
        ]);
        $response->assertStatus(422);
    }

    public function test_program_create_rejects_nonexistent_faculty(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/programs', [
            'name'       => 'Test',
            'faculty_id' => 9999,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_program(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $faculty  = $this->faculty();
        $program  = Program::create(['name' => 'Original', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/programs/{$program->id}", [
            'name'       => 'Actualizado',
            'faculty_id' => $faculty->id,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('programs', ['id' => $program->id, 'name' => 'Actualizado']);
    }

    public function test_program_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $faculty  = $this->faculty();
        $response = $this->putJson('/api/programs/9999', [
            'name'       => 'X',
            'faculty_id' => $faculty->id,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(404);
    }

    public function test_program_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $program  = Program::create(['name' => 'Test', 'faculty_id' => $this->faculty()->id, 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/programs/{$program->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('programs', ['id' => $program->id]);
        $this->assertDatabaseMissing('programs', ['id' => $program->id, 'status' => 'ACTIVO']);
    }
}
