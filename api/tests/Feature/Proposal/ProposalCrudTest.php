<?php

namespace Tests\Feature\Proposal;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proposal;
use App\Models\Area;
use App\Models\Faculty;
use App\Models\Program;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF11 — Gestión de Propuestas de investigación (CU25)
 */
class ProposalCrudTest extends TestCase
{
    use RefreshDatabase;

    private function area(): Area
    {
        return Area::create(['name' => 'Área Test', 'code' => 'AT-' . uniqid(), 'status' => 'ACTIVO']);
    }

    private function programActivo(): Program
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        return Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'user_id'     => User::factory()->create(['role' => 'ESTUDIANTE'])->id,
            'program_id'  => $this->programActivo()->id,
            'areas'       => [$this->area()->id],
            'title'       => 'Investigación sobre IA',
            'description' => 'Propuesta para aplicar IA en la educación superior de la región.',
            'phone'       => '3001234567',
            'status'      => 'PENDIENTE',
        ], $overrides);
    }

    public function test_unauthenticated_cannot_access_proposals(): void
    {
        $response = $this->getJson('/api/proposals');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_proposals(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/proposals');
        $response->assertStatus(200)->assertJsonStructure(['proposals']);
    }

    public function test_authenticated_user_can_create_proposal(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/proposals', $this->payload());
        $response->assertStatus(201);
        $this->assertDatabaseHas('proposals', ['title' => 'Investigación sobre IA']);
    }

    public function test_proposal_can_have_multiple_areas(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $areaA = $this->area();
        $areaB = $this->area();
        $response = $this->postJson('/api/proposals', $this->payload(['areas' => [$areaA->id, $areaB->id]]));
        $response->assertStatus(201);
        $proposal = Proposal::latest('id')->first();
        $this->assertCount(2, $proposal->areas);
    }

    public function test_proposal_create_requires_all_fields(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/proposals', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['user_id', 'program_id', 'areas', 'title', 'description', 'status']);
    }

    /* E1 / paso 4: descripción entre 20 y 2000 caracteres */
    public function test_proposal_description_requires_minimum_length(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/proposals', $this->payload(['description' => 'muy corta']));
        $response->assertStatus(422)->assertJsonValidationErrors(['description']);
    }

    public function test_proposal_rejects_inactive_program(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $faculty  = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $inactive = Program::create(['name' => 'P inactivo', 'faculty_id' => $faculty->id, 'status' => 'INACTIVO']);
        $response = $this->postJson('/api/proposals', $this->payload(['program_id' => $inactive->id]));
        $response->assertStatus(422)->assertJsonValidationErrors(['program_id']);
    }

    public function test_proposal_rejects_inactive_area(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $inactive = Area::create(['name' => 'A inactiva', 'code' => 'AI-' . uniqid(), 'status' => 'INACTIVO']);
        $response = $this->postJson('/api/proposals', $this->payload(['areas' => [$inactive->id]]));
        $response->assertStatus(422)->assertJsonValidationErrors(['areas.0']);
    }

    /* E3 / RN15: máximo 5 propuestas en 24 horas */
    public function test_student_is_rate_limited_after_5_proposals_in_24h(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($student);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/proposals', $this->payload(['user_id' => $student->id]))
                ->assertStatus(201);
        }
        $this->postJson('/api/proposals', $this->payload(['user_id' => $student->id]))
            ->assertStatus(429);
    }

    public function test_proposal_status_must_be_valid_value(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/proposals', $this->payload(['status' => 'INVALIDO']));
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_proposal(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $program  = $this->programActivo();
        $area     = $this->area();
        $user     = User::factory()->create(['role' => 'ESTUDIANTE']);
        $proposal = Proposal::create([
            'user_id'     => $user->id,
            'program_id'  => $program->id,
            'title'       => 'Original',
            'description' => 'Descripción original con longitud suficiente para pasar validación.',
            'status'      => 'PENDIENTE',
        ]);
        $proposal->areas()->attach($area->id);

        $response = $this->putJson("/api/proposals/{$proposal->id}", $this->payload([
            'user_id' => $user->id, 'program_id' => $program->id, 'areas' => [$area->id],
            'title' => 'Actualizada', 'status' => 'APROBADA',
        ]));
        $response->assertStatus(200);
        $this->assertDatabaseHas('proposals', ['id' => $proposal->id, 'status' => 'APROBADA']);
    }

    public function test_proposal_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->putJson('/api/proposals/9999', $this->payload());
        $response->assertStatus(404);
    }

    public function test_proposal_create_rejects_nonexistent_user(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/proposals', $this->payload(['user_id' => 9999]));
        $response->assertStatus(422);
    }
}
