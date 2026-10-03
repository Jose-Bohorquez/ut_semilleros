<?php

namespace Tests\Feature\Request;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\MembershipRequest;
use App\Models\Audit;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU24 — Gestionar solicitudes recibidas (RF10, RN05-RN07)
 */
class RequestManagementTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(string $name = 'Semillero A'): Seedbed
    {
        $faculty = Faculty::create(['name' => "Facultad {$name}", 'status' => 'ACTIVO']);
        $program = Program::create(['name' => "Programa {$name}", 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $seedbed = Seedbed::create(['name' => $name, 'status' => 'ACTIVO']);
        $seedbed->programs()->attach($program->id);
        return $seedbed;
    }

    private function leaderOf(Seedbed $seedbed): User
    {
        $leader = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $seedbed->users()->attach($leader->id, ['role' => 'LIDER']);
        return $leader;
    }

    private function pending(Seedbed $seedbed, ?User $student = null, array $extra = []): MembershipRequest
    {
        $student ??= User::factory()->create(['role' => 'ESTUDIANTE']);
        return MembershipRequest::create(array_merge([
            'user_id'    => $student->id,
            'seedbed_id' => $seedbed->id,
            'program_id' => $seedbed->programs()->first()->id,
            'status'     => 'PENDIENTE',
            'phone'      => '3001234567',
            'message'    => 'Quiero unirme al semillero para investigar.',
        ], $extra));
    }

    /* ───── Listado (pasos 1-2) y alcance RN06 ───── */

    public function test_leader_only_sees_requests_of_his_own_seedbeds(): void
    {
        $mine   = $this->seedbed('Mio');
        $other  = $this->seedbed('Ajeno');
        $leader = $this->leaderOf($mine);
        $reqMine  = $this->pending($mine);
        $reqOther = $this->pending($other);

        Sanctum::actingAs($leader);
        $res = $this->getJson('/api/requests')->assertOk();

        $ids = collect($res->json('requests'))->pluck('id');
        $this->assertTrue($ids->contains($reqMine->id));
        $this->assertFalse($ids->contains($reqOther->id));
        $this->assertSame([$mine->id], collect($res->json('seedbeds'))->pluck('id')->all());
    }

    public function test_admin_and_administrativo_see_all_requests(): void
    {
        $a = $this->seedbed('A');
        $b = $this->seedbed('B');
        $this->pending($a);
        $this->pending($b);

        foreach (['ADMIN_SISTEMA', 'ADMINISTRATIVO'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->assertCount(2, $this->getJson('/api/requests')->assertOk()->json('requests'), $role);
        }
    }

    public function test_list_includes_student_seedbed_program_and_date(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->getJson('/api/requests')->assertOk()->assertJsonStructure([
            'requests' => [['id', 'status', 'created_at',
                'user' => ['id', 'name', 'email'],
                'seedbed' => ['id', 'name'],
                'program' => ['id', 'name']]],
        ]);
    }

    public function test_student_cannot_list_requests(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $this->getJson('/api/requests')->assertStatus(403);
    }

    /* ───── A2: filtros ───── */

    public function test_filter_by_status_and_seedbed(): void
    {
        $a = $this->seedbed('A');
        $b = $this->seedbed('B');
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        $pendA = $this->pending($a);
        $this->pending($a, null, ['status' => 'RECHAZADA', 'reason' => 'No hay cupo']);
        $this->pending($b);

        Sanctum::actingAs($admin);
        $ids = collect($this->getJson("/api/requests?status=PENDIENTE&seedbed_id={$a->id}")
            ->assertOk()->json('requests'))->pluck('id')->all();

        $this->assertSame([$pendA->id], $ids);
    }

    public function test_filter_by_date_range(): void
    {
        $seedbed = $this->seedbed();
        $admin   = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        $old     = $this->pending($seedbed);
        $old->forceFill(['created_at' => '2026-01-10 10:00:00'])->save();
        $recent  = $this->pending($seedbed);
        $recent->forceFill(['created_at' => '2026-09-20 10:00:00'])->save();

        Sanctum::actingAs($admin);
        $ids = collect($this->getJson('/api/requests?from=2026-09-01&to=2026-09-30')
            ->assertOk()->json('requests'))->pluck('id')->all();

        $this->assertSame([$recent->id], $ids);
    }

    public function test_filter_rejects_invalid_status_and_inverted_dates(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->getJson('/api/requests?status=XYZ')->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->getJson('/api/requests?from=2026-09-30&to=2026-09-01')->assertStatus(422)->assertJsonValidationErrors(['to']);
    }

    /* ───── Detalle (pasos 3-4) y E3 ───── */

    public function test_leader_can_view_detail_of_own_request(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->getJson("/api/requests/{$req->id}")->assertOk()
            ->assertJsonPath('request.message', 'Quiero unirme al semillero para investigar.')
            ->assertJsonPath('request.user.email', $req->user->email);
    }

    public function test_e3_leader_cannot_view_request_of_foreign_seedbed(): void
    {
        $mine  = $this->seedbed('Mio');
        $other = $this->seedbed('Ajeno');
        $leader = $this->leaderOf($mine);
        $req    = $this->pending($other);

        Sanctum::actingAs($leader);
        $this->getJson("/api/requests/{$req->id}")->assertStatus(403);
    }

    public function test_my_route_is_not_shadowed_by_show(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $this->getJson('/api/requests/my')->assertOk()->assertJsonStructure(['requests']);
    }

    /* ───── Aprobar (pasos 5-6) ───── */

    public function test_leader_approves_with_optional_response(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", [
            'status' => 'APROBADA',
            'reason' => 'Bienvenido, la primera reunión es el lunes.',
        ])->assertOk()->assertJsonPath('request.status', 'APROBADA');

        $req->refresh();
        $this->assertSame('APROBADA', $req->status);
        $this->assertSame('Bienvenido, la primera reunión es el lunes.', $req->reason);
        $this->assertSame($leader->id, (int) $req->reviewed_by);
        $this->assertNotNull($req->reviewed_at);
    }

    public function test_leader_approves_without_response(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertOk();

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'APROBADA', 'reason' => null]);
    }

    public function test_resolution_is_audited(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertOk();

        $this->assertTrue(Audit::where([
            'user_id'    => $leader->id,
            'action'     => 'STATUS_CHANGE',
            'table_name' => 'requests',
            'record_id'  => $req->id,
        ])->exists());
    }

    /* ───── A1 / E1: rechazar ───── */

    public function test_leader_rejects_with_reason(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", [
            'status' => 'RECHAZADA',
            'reason' => 'No hay cupos disponibles este semestre.',
        ])->assertOk();

        $this->assertDatabaseHas('requests', [
            'id' => $req->id, 'status' => 'RECHAZADA', 'reason' => 'No hay cupos disponibles este semestre.',
        ]);
    }

    public function test_e1_reject_without_reason_is_blocked_and_stays_pending(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'RECHAZADA'])
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'RECHAZADA', 'reason' => '   '])
            ->assertStatus(422)->assertJsonValidationErrors(['reason']);

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'PENDIENTE']);
    }

    public function test_status_pendiente_is_not_a_valid_resolution(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'PENDIENTE'])
            ->assertStatus(422)->assertJsonValidationErrors(['status']);
    }

    /* ───── E2: ya resuelta ───── */

    public function test_e2_already_resolved_request_cannot_be_changed(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed, null, ['status' => 'RECHAZADA', 'reason' => 'Semillero inactivo']);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])
            ->assertStatus(409)
            ->assertJsonPath('message', 'Esta solicitud ya fue resuelta.');

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'RECHAZADA', 'reason' => 'Semillero inactivo']);
    }

    public function test_e2_second_resolution_of_same_request_is_blocked(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $req     = $this->pending($seedbed);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertOk();
        $this->putJson("/api/requests/{$req->id}/update-status", [
            'status' => 'RECHAZADA', 'reason' => 'Cambié de opinión.',
        ])->assertStatus(409);

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'APROBADA']);
    }

    /* ───── E3 + roles ───── */

    public function test_e3_leader_cannot_resolve_request_of_foreign_seedbed(): void
    {
        $mine  = $this->seedbed('Mio');
        $other = $this->seedbed('Ajeno');
        $leader = $this->leaderOf($mine);
        $req    = $this->pending($other);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertStatus(403);

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'PENDIENTE']);
    }

    public function test_a3_administrativo_is_read_only(): void
    {
        $seedbed = $this->seedbed();
        $req     = $this->pending($seedbed);

        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $this->getJson("/api/requests/{$req->id}")->assertOk();
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertStatus(403);

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'PENDIENTE']);
    }

    public function test_admin_sistema_can_resolve_any_request(): void
    {
        $seedbed = $this->seedbed();
        $req     = $this->pending($seedbed);

        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertOk();

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'APROBADA']);
    }

    public function test_student_cannot_resolve_requests_even_his_own(): void
    {
        $seedbed = $this->seedbed();
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $req     = $this->pending($seedbed, $student);

        Sanctum::actingAs($student);
        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertStatus(403);

        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'PENDIENTE']);
    }

    /* ───── Paso 8: el estudiante ve el resultado en CU23 ───── */

    public function test_student_sees_new_status_and_response_in_my_requests(): void
    {
        $seedbed = $this->seedbed();
        $leader  = $this->leaderOf($seedbed);
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $req     = $this->pending($seedbed, $student);

        Sanctum::actingAs($leader);
        $this->putJson("/api/requests/{$req->id}/update-status", [
            'status' => 'APROBADA', 'reason' => 'Te esperamos el lunes.',
        ])->assertOk();

        Sanctum::actingAs($student);
        $mine = $this->getJson('/api/requests/my')->assertOk()->json('requests.0');
        $this->assertSame('APROBADA', $mine['status']);
        $this->assertSame('Te esperamos el lunes.', $mine['reason']);
    }
}
