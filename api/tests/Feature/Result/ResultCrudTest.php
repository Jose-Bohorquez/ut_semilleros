<?php

namespace Tests\Feature\Result;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Area;
use App\Models\Seedbed;
use App\Models\Result;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF09 — Gestión de Resultados (CU20)
 */
class ResultCrudTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $area = Area::create(['name' => 'Área Test', 'code' => 'AT-' . uniqid(), 'status' => 'ACTIVO']);
        $seedbed = Seedbed::create(['name' => 'Semillero Test', 'status' => 'ACTIVO']);
        $seedbed->programs()->attach($program->id);
        $seedbed->areas()->attach($area->id);
        return $seedbed;
    }

    /** Semillero + líder ya asignado como responsable (pivot). */
    private function seedbedWithLeader(): array
    {
        $seedbed = $this->seedbed();
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $seedbed->users()->attach($lider->id, ['role' => 'LIDER']);
        return [$seedbed, $lider];
    }

    public function test_unauthenticated_cannot_access_results(): void
    {
        $response = $this->getJson('/api/results');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_results(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->getJson('/api/results');
        $response->assertStatus(200)->assertJsonStructure(['results']);
    }

    public function test_authenticated_user_can_create_result(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/results', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Publicación de artículo científico',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('results', ['seedbed_id' => $seedbed->id]);
    }

    public function test_result_can_be_created_with_optional_date(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/results', [
            'seedbed_id'   => $seedbed->id,
            'content'      => 'Publicación de artículo científico',
            'result_date'  => '2026-09-30',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('results', ['seedbed_id' => $seedbed->id, 'result_date' => '2026-09-30']);
    }

    public function test_result_create_requires_seedbed_and_content(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/results', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['seedbed_id', 'content']);
    }

    public function test_result_create_rejects_nonexistent_seedbed(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $response = $this->postJson('/api/results', [
            'seedbed_id' => 9999,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ]);
        $response->assertStatus(422);
    }

    /* CU20 paso 6 / E1: contenido entre 10 y 2000 caracteres */
    public function test_result_content_requires_minimum_length(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/results', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'corto',
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['content']);
    }

    public function test_result_content_rejects_over_max_length(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $response = $this->postJson('/api/results', [
            'seedbed_id' => $seedbed->id,
            'content'    => str_repeat('a', 2001),
        ]);
        $response->assertStatus(422)->assertJsonValidationErrors(['content']);
    }

    public function test_authenticated_user_can_update_result(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $result   = Result::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido original de prueba', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/results/{$result->id}", [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido actualizado de prueba',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('results', ['id' => $result->id, 'content' => 'Contenido actualizado de prueba']);
    }

    public function test_result_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $seedbed  = $this->seedbed();
        $response = $this->putJson('/api/results/9999', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ]);
        $response->assertStatus(404);
    }

    public function test_result_status_can_be_toggled_without_deletion(): void
    {
        [$seedbed, $lider] = $this->seedbedWithLeader();
        Sanctum::actingAs($lider);
        $result   = Result::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido de prueba', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/results/{$result->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('results', ['id' => $result->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('results', ['id' => $result->id]);
    }

    /* ───── RN06 (CU20 E2): el líder solo gestiona resultados de sus semilleros ───── */

    public function test_leader_cannot_create_result_for_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->postJson('/api/results', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ])->assertStatus(403);
    }

    public function test_leader_cannot_update_result_of_seedbed_they_do_not_lead(): void
    {
        $seedbed = $this->seedbed();
        $result = Result::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido original', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->putJson("/api/results/{$result->id}", [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido modificado sin permiso',
        ])->assertStatus(403);
    }

    public function test_leader_cannot_toggle_status_of_result_they_do_not_own(): void
    {
        $seedbed = $this->seedbed();
        $result = Result::create(['seedbed_id' => $seedbed->id, 'content' => 'Contenido de prueba', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->putJson("/api/results/{$result->id}/toggle-status")->assertStatus(403);
    }

    public function test_admin_can_manage_any_seedbed_result(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->postJson('/api/results', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido creado por el administrador',
        ])->assertStatus(201);
    }

    /* CU20 A3 / actor secundario: Administrativo es solo consulta (2026-09-30,
       mismo criterio que CU16/CU19). */
    public function test_administrativo_cannot_write_results(): void
    {
        $seedbed = $this->seedbed();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $this->postJson('/api/results', [
            'seedbed_id' => $seedbed->id,
            'content'    => 'Contenido de prueba con longitud suficiente',
        ])->assertStatus(403);
    }

    public function test_administrativo_can_list_results(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $this->getJson('/api/results')->assertStatus(200);
    }
}
