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
        return Seedbed::create(['name' => 'Semillero Sec', 'program_id' => $program->id, 'status' => 'ACTIVO']);
    }

    /* ── C-02: el cliente no controla user_id/status en postulaciones ── */

    public function test_student_request_is_forced_to_self_and_pending(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $other   = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($student);

        $response = $this->postJson('/api/requests', [
            'user_id'    => $other->id,
            'seedbed_id' => $this->seedbed()->id,
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
            'user_id' => $other->id, 'seedbed_id' => $seedbed->id, 'status' => 'PENDIENTE',
        ])->assertStatus(422);
    }

    public function test_staff_can_still_create_request_for_a_student(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);

        $this->postJson('/api/requests', [
            'user_id' => $student->id, 'seedbed_id' => $this->seedbed()->id, 'status' => 'PENDIENTE',
        ])->assertStatus(201);
        $this->assertDatabaseHas('requests', ['user_id' => $student->id]);
    }

    /* ── C-02: el cliente no controla user_id/status en propuestas ── */

    public function test_student_proposal_is_forced_to_self_and_pending(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $other   = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($student);

        $area = Area::create(['name' => 'A', 'code' => 'A-' . uniqid(), 'status' => 'ACTIVO']);
        $this->postJson('/api/proposals', [
            'user_id' => $other->id, 'area_id' => $area->id, 'title' => 'qa_sec', 'description' => 'd', 'status' => 'APROBADA',
        ])->assertStatus(201);

        $this->assertDatabaseHas('proposals', ['title' => 'qa_sec', 'user_id' => $student->id, 'status' => 'PENDIENTE']);
    }

    public function test_student_cannot_reassign_own_proposal_to_another_user(): void
    {
        $student  = User::factory()->create(['role' => 'ESTUDIANTE']);
        $other    = User::factory()->create(['role' => 'ESTUDIANTE']);
        $area     = Area::create(['name' => 'A', 'code' => 'A-' . uniqid(), 'status' => 'ACTIVO']);
        $proposal = Proposal::create(['user_id' => $student->id, 'area_id' => $area->id, 'title' => 't', 'description' => 'd', 'status' => 'PENDIENTE']);
        Sanctum::actingAs($student);

        $this->putJson("/api/proposals/{$proposal->id}", [
            'user_id' => $other->id, 'area_id' => $area->id, 'title' => 't2', 'description' => 'd2', 'status' => 'APROBADA',
        ])->assertStatus(200);

        $proposal->refresh();
        $this->assertSame($student->id, $proposal->user_id);
        $this->assertSame('PENDIENTE', $proposal->status);
        $this->assertSame('t2', $proposal->title);
    }

    /* ── C-16: el líder no cambia el estado de un coordinador por PUT ── */

    public function test_leader_cannot_change_coordinator_status_via_update(): void
    {
        $coord = Coordinator::create(['name' => 'C', 'email' => 'qa_sec_c@example.test', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->putJson("/api/coordinators/{$coord->id}", [
            'name' => 'C2', 'email' => 'qa_sec_c@example.test', 'status' => 'INACTIVO',
        ])->assertStatus(200);

        $coord->refresh();
        $this->assertSame('ACTIVO', $coord->status);
        $this->assertSame('C2', $coord->name);
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

    public function test_leader_creates_coordinators_always_active(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));

        $this->postJson('/api/coordinators', [
            'name' => 'C', 'email' => 'qa_sec_c3@example.test', 'status' => 'INACTIVO',
        ])->assertStatus(201);

        $this->assertDatabaseHas('coordinators', ['email' => 'qa_sec_c3@example.test', 'status' => 'ACTIVO']);
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
