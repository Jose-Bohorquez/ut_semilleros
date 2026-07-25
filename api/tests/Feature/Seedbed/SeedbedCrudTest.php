<?php

namespace Tests\Feature\Seedbed;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
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
        $program  = $this->program();
        $response = $this->postJson('/api/seedbeds', [
            'name'       => 'Semillero Innovación',
            'program_id' => $program->id,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('seedbeds', ['name' => 'Semillero Innovación']);
    }

    public function test_seedbed_create_requires_program_id(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', ['name' => 'Sin programa', 'status' => 'ACTIVO']);
        $response->assertStatus(422);
    }

    public function test_seedbed_create_rejects_nonexistent_program(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/seedbeds', [
            'name'       => 'Test',
            'program_id' => 9999,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $program  = $this->program();
        $seedbed  = Seedbed::create(['name' => 'Original', 'program_id' => $program->id, 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}", [
            'name'       => 'Actualizado',
            'program_id' => $program->id,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'name' => 'Actualizado']);
    }

    public function test_seedbed_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $program  = $this->program();
        $response = $this->putJson('/api/seedbeds/9999', [
            'name'       => 'X',
            'program_id' => $program->id,
            'status'     => 'ACTIVO',
        ]);
        $response->assertStatus(404);
    }

    public function test_seedbed_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = Seedbed::create(['name' => 'Test', 'program_id' => $this->program()->id, 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('seedbeds', ['id' => $seedbed->id]);
    }
}
