<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\Proposal;
use App\Models\Area;
use App\Models\Coordinator;
use App\Models\MembershipRequest;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Regresión de los hallazgos de seguridad de la validación de CU (2026-09-27).
 * Ver CLAUDE.md § Prohibiciones y docs/qa/ (local).
 */
class SecurityRegressionTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Sec', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Sec', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $seedbed = Seedbed::create(['name' => 'Semillero Sec', 'status' => 'ACTIVO']);
        $seedbed->programs()->attach($program->id);
        return $seedbed;
    }

    /* CU16 A2: Administrativo consulta pero no edita semilleros (alineado
       2026-09-30, revierte la decisión previa del 2026-07-28). */
    public function test_administrativo_cannot_write_seedbeds(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $seedbed = $this->seedbed();

        $this->postJson('/api/seedbeds', ['name' => 'X'])->assertStatus(403);
        $this->putJson("/api/seedbeds/{$seedbed->id}", ['name' => 'Y'])->assertStatus(403);
        $this->putJson("/api/seedbeds/{$seedbed->id}/toggle-status")->assertStatus(403);
    }

    /* ── C-02: el cliente no controla user_id/status en postulaciones ── */

    public function test_student_request_is_forced_to_self_and_pending(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $other   = User::factory()->create(['role' => 'ESTUDIANTE']);
        $seedbed = $this->seedbed();
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/requests', [
            'user_id'    => $other->id,
            'seedbed_id' => $seedbed->id,
            'program_id' => $seedbed->programs()->first()->id,
            'phone'      => '3001234567',
            'message'    => 'Quiero postularme a este semillero por interés académico.',
            'status'     => 'APROBADA',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('requests', ['user_id' => $student->id, 'status' => 'PENDIENTE']);
        $this->assertDatabaseMissing('requests', ['user_id' => $other->id]);
        $this->assertDatabaseMissing('requests', ['status' => 'APROBADA']);
    }

    public function test_student_cannot_bypass_single_active_request_by_spoofing_user_id(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $other   = User::factory()->create(['role' => 'ESTUDIANTE']);
        $seedbed = $this->seedbed();
        MembershipRequest::create(['user_id' => $student->id, 'seedbed_id' => $seedbed->id, 'status' => 'PENDIENTE']);
        Sanctum::actingAs($student);

        /* Mandar el id de otro ya no evade la regla: se evalúa contra el propio. */
        $this->postJson('/api/requests', [
            'user_id' => $other->id, 'seedbed_id' => $seedbed->id,
            'program_id' => $seedbed->programs()->first()->id,
            'phone' => '3001234567', 'message' => 'Mensaje de prueba con longitud suficiente.',
            'status' => 'PENDIENTE',
        ])->assertStatus(409);
    }

    /* CU22 pasó a ser solo del estudiante (2026-10-03): el personal ya no crea solicitudes a nombre de un estudiante. */
    public function test_staff_cannot_create_a_request_for_a_student(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $seedbed = $this->seedbed();

        $this->postJson('/api/requests', [
            'user_id' => $student->id, 'seedbed_id' => $seedbed->id,
            'program_id' => $seedbed->programs()->first()->id,
            'phone' => '3001234567', 'message' => 'Mensaje de prueba con longitud suficiente.',
            'status' => 'PENDIENTE',
        ])->assertStatus(403);
        $this->assertDatabaseMissing('requests', ['user_id' => $student->id]);
    }

    /* ── C-02: el cliente no controla user_id/status en propuestas ── */

    public function test_student_proposal_is_forced_to_self_and_pending(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $other   = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($student);

        $area    = Area::create(['name' => 'A', 'code' => 'A-' . uniqid(), 'status' => 'ACTIVO']);
        $faculty = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'P', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $this->postJson('/api/proposals', [
            'user_id' => $other->id, 'program_id' => $program->id, 'areas' => [$area->id],
            'title' => 'qa_sec', 'description' => 'Descripción con longitud suficiente para pasar validación.', 'status' => 'VIABLE',
        ])->assertStatus(201);

        $this->assertDatabaseHas('proposals', ['title' => 'qa_sec', 'user_id' => $student->id, 'status' => 'RECIBIDA']);
    }

    public function test_student_cannot_reassign_own_proposal_to_another_user(): void
    {
        $student  = User::factory()->create(['role' => 'ESTUDIANTE']);
        $other    = User::factory()->create(['role' => 'ESTUDIANTE']);
        $area     = Area::create(['name' => 'A', 'code' => 'A-' . uniqid(), 'status' => 'ACTIVO']);
        $faculty  = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $program  = Program::create(['name' => 'P', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $proposal = Proposal::create(['user_id' => $student->id, 'program_id' => $program->id, 'title' => 't', 'description' => 'Descripción con longitud suficiente.', 'status' => 'RECIBIDA']);
        $proposal->areas()->attach($area->id);
        Sanctum::actingAs($student);

        $this->putJson("/api/proposals/{$proposal->id}", [
            'user_id' => $other->id, 'program_id' => $program->id, 'areas' => [$area->id],
            'title' => 't2', 'description' => 'Descripción actualizada con longitud suficiente.', 'status' => 'VIABLE',
        ])->assertStatus(200);

        $proposal->refresh();
        $this->assertSame($student->id, $proposal->user_id);
        $this->assertSame('RECIBIDA', $proposal->status);
        $this->assertSame('t2', $proposal->title);
    }

    /* ── C-16 (histórico) / CU12-A5 (2026-09-29): el líder ya no escribe
       coordinadores en absoluto — antes solo se le bloqueaba cambiar el
       estado por PUT, ahora el PUT entero le da 403 (alineado a la spec). ── */

    public function test_leader_cannot_update_coordinator_at_all(): void
    {
        $coord = Coordinator::create(['name' => 'C', 'email' => 'qa_sec_c@example.test', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->putJson("/api/coordinators/{$coord->id}", [
            'name' => 'C2', 'email' => 'qa_sec_c@example.test', 'status' => 'INACTIVO',
        ])->assertStatus(403);

        $coord->refresh();
        $this->assertSame('ACTIVO', $coord->status);
        $this->assertSame('C', $coord->name);
    }

    public function test_admin_can_still_change_coordinator_status_via_update(): void
    {
        $coord = Coordinator::create(['name' => 'C', 'email' => 'qa_sec_c2@example.test', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));

        $this->putJson("/api/coordinators/{$coord->id}", [
            'name' => 'C', 'email' => 'qa_sec_c2@example.test', 'status' => 'INACTIVO',
        ])->assertStatus(200);

        $this->assertSame('INACTIVO', $coord->fresh()->status);
    }

    /* ── C-04: un usuario inactivo pierde el acceso con su token viejo ── */

    public function test_inactive_user_existing_token_is_rejected(): void
    {
        $user  = User::factory()->create(['role' => 'ESTUDIANTE']);
        $token = $user->createToken('auth')->plainTextToken;

        $this->withToken($token)->getJson('/api/me')->assertStatus(200);

        $user->update(['status' => 'INACTIVO']);
        $this->app['auth']->forgetGuards();

        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }

    public function test_inactivating_user_revokes_all_tokens(): void
    {
        $user = User::factory()->create(['role' => 'ESTUDIANTE']);
        $user->createToken('a');
        $user->createToken('b');
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));

        $this->putJson("/api/users/{$user->id}/toggle-status")->assertStatus(200);

        $this->assertSame('INACTIVO', $user->fresh()->status);
        $this->assertSame(0, $user->tokens()->count());
    }

    /* CU12-A5 (2026-09-29): el líder ya no puede crear coordinadores en
       absoluto (antes se le permitía, siempre en estado ACTIVO). */
    public function test_leader_cannot_create_coordinators(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->postJson('/api/coordinators', [
            'name' => 'C', 'email' => 'qa_sec_c3@example.test', 'status' => 'INACTIVO',
        ])->assertStatus(403);

        $this->assertDatabaseMissing('coordinators', ['email' => 'qa_sec_c3@example.test']);
    }

    public function test_inactivating_user_via_update_form_revokes_tokens(): void
    {
        $user = User::factory()->create(['role' => 'ESTUDIANTE']);
        $user->createToken('a');
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));

        $this->putJson("/api/users/{$user->id}", [
            'name' => $user->name, 'email' => $user->email, 'role' => 'ESTUDIANTE', 'status' => 'INACTIVO',
        ])->assertStatus(200);

        $this->assertSame(0, $user->tokens()->count());
    }
}
