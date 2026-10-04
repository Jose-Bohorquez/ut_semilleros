<?php

namespace Tests\Feature\UseCases;

use App\Models\Audit;
use App\Models\Faculty;
use App\Models\MembershipRequest;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** El estudiante puede cancelar SU postulación mientras esté pendiente (por si se equivocó). */
class CU22CancelRequestTest extends TestCase
{
    use RefreshDatabase;

    private Program $program;

    protected function setUp(): void
    {
        parent::setUp();
        $f = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $this->program = Program::create(['name' => 'P', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'ESTUDIANTE', 'status' => 'ACTIVO']);
    }

    private function seedbed(string $name = 'A'): Seedbed
    {
        $s = Seedbed::create(['name' => $name, 'status' => 'ACTIVO']);
        $s->programs()->attach($this->program->id);

        return $s;
    }

    private function request(User $u, Seedbed $s, string $status = 'PENDIENTE'): MembershipRequest
    {
        return MembershipRequest::create([
            'user_id' => $u->id, 'seedbed_id' => $s->id, 'program_id' => $this->program->id, 'status' => $status,
            'phone' => '3001234567', 'message' => 'Quiero unirme al semillero por favor.',
        ]);
    }

    public function test_the_student_cancels_his_pending_request_and_it_is_kept_as_cancelada(): void
    {
        $u = $this->student(); $s = $this->seedbed();
        $r = $this->request($u, $s);
        Sanctum::actingAs($u);

        $res = $this->putJson("/api/requests/{$r->id}/cancel")->assertOk();

        $this->assertSame('CANCELADA', $res->json('request.status'));
        $this->assertDatabaseHas('requests', ['id' => $r->id, 'status' => 'CANCELADA', 'reason' => 'Cancelada por el estudiante.']);
        $mine = $this->getJson('/api/requests/my')->assertOk()->json('requests');
        $this->assertSame(['CANCELADA'], array_column($mine, 'status'), 'sigue en «Mis solicitudes»');
    }

    public function test_after_cancelling_the_student_is_free_to_apply_again(): void
    {
        $u = $this->student(); $a = $this->seedbed('A'); $b = $this->seedbed('B');
        $r = $this->request($u, $a);
        Sanctum::actingAs($u);
        $this->assertFalse($this->getJson('/api/requests/eligibility?seedbed_id=' . $b->id)->json('can_apply'));

        $this->putJson("/api/requests/{$r->id}/cancel")->assertOk();

        $this->assertTrue($this->getJson('/api/requests/eligibility?seedbed_id=' . $b->id)->json('can_apply'));
        $this->postJson('/api/requests', [
            'seedbed_id' => $b->id, 'program_id' => $this->program->id, 'phone' => '3001234567', 'message' => 'Me equivoqué de semillero, quiero este.',
        ])->assertStatus(201);
    }

    public function test_the_eligibility_exposes_the_pending_request_id_so_the_app_can_offer_cancelling(): void
    {
        $u = $this->student(); $s = $this->seedbed();
        $r = $this->request($u, $s);
        Sanctum::actingAs($u);

        $e = $this->getJson('/api/requests/eligibility?seedbed_id=' . $s->id)->json();

        $this->assertSame(['pending_here', $r->id], [$e['state'], $e['request_id']]);
    }

    public function test_only_pending_requests_can_be_cancelled(): void
    {
        $u = $this->student(); $s = $this->seedbed();
        Sanctum::actingAs($u);
        foreach (['APROBADA', 'RECHAZADA', 'CANCELADA'] as $status) {
            $r = $this->request($u, $s, $status);
            $this->putJson("/api/requests/{$r->id}/cancel")->assertStatus(409);
            $this->assertSame($status, $r->fresh()->status, "no cambia {$status}");
        }
    }

    public function test_a_student_cannot_cancel_somebody_elses_request(): void
    {
        $owner = $this->student(); $other = $this->student(); $s = $this->seedbed();
        $r = $this->request($owner, $s);
        Sanctum::actingAs($other);

        $this->putJson("/api/requests/{$r->id}/cancel")->assertStatus(404);

        $this->assertSame('PENDIENTE', $r->fresh()->status);
    }

    public function test_only_students_can_use_it(): void
    {
        $s = $this->seedbed(); $r = $this->request($this->student(), $s);
        $this->putJson("/api/requests/{$r->id}/cancel")->assertStatus(401);
        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO', 'ADMIN_SISTEMA'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role, 'status' => 'ACTIVO']));
            $this->putJson("/api/requests/{$r->id}/cancel")->assertStatus(403);
        }
        $this->assertSame('PENDIENTE', $r->fresh()->status);
    }

    public function test_the_cancellation_is_audited_with_old_and_new_values(): void
    {
        $u = $this->student(); $s = $this->seedbed(); $r = $this->request($u, $s);
        DB::table('audits')->delete();
        Sanctum::actingAs($u);

        $this->putJson("/api/requests/{$r->id}/cancel")->assertOk();

        $row = Audit::where('table_name', 'requests')->where('record_id', $r->id)->latest('id')->firstOrFail();
        $this->assertSame('STATUS_CHANGE', $row->action);
        $this->assertSame(['PENDIENTE', 'CANCELADA'], [$row->old_values['status'], $row->new_values['status']]);
        $this->assertSame($u->id, $row->user_id);
    }

    public function test_cancelled_requests_do_not_count_in_the_reports_and_can_be_filtered_by_staff(): void
    {
        $u = $this->student(); $s = $this->seedbed();
        $this->request($u, $s, 'CANCELADA'); $this->request($this->student(), $s, 'PENDIENTE');
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO', 'status' => 'ACTIVO']));

        $rep = $this->getJson('/api/reports')->assertOk()->json('reports.requests_by_seedbed');
        $this->assertSame([1, 1], [$rep['total'], $rep['rows'][0]['pendientes']], 'solo la pendiente cuenta');

        $list = $this->getJson('/api/requests?status=CANCELADA')->assertOk()->json('requests');
        $this->assertCount(1, $list);
        $this->assertSame('CANCELADA', $list[0]['status']);
    }

    public function test_the_migration_adds_the_new_status_and_rolls_back_without_losing_rows(): void
    {
        $path = 'database/migrations/2026_10_04_000002_requests_add_cancelada_status.php';
        $u = $this->student(); $s = $this->seedbed();
        $r = $this->request($u, $s, 'CANCELADA');

        $this->artisan('migrate:rollback', ['--path' => $path, '--force' => true])->assertExitCode(0);
        $this->assertSame('RECHAZADA', DB::table('requests')->where('id', $r->id)->value('status'));
        $this->artisan('migrate', ['--path' => $path, '--force' => true])->assertExitCode(0);
        DB::table('requests')->where('id', $r->id)->update(['status' => 'CANCELADA']);
        $this->assertSame('CANCELADA', DB::table('requests')->where('id', $r->id)->value('status'));
    }
}
