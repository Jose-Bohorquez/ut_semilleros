<?php

namespace Tests\Feature\UseCases;

use App\Models\Faculty;
use App\Models\MembershipRequest;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\SeedbedMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * CU22 — regla de Jose (2026-10-04): para postularse a un semillero el estudiante no debe tener NINGUNO asociado
 * ni activo (solicitud pendiente, integrante activo o aprobada sin registrar). Si sale del semillero, queda libre.
 */
class CU22EligibilityTest extends TestCase
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
        return User::factory()->create(['role' => 'ESTUDIANTE', 'status' => 'ACTIVO', 'email' => 'est' . uniqid() . '@ut.edu.co']);
    }

    private function seedbed(string $name, string $status = 'ACTIVO'): Seedbed
    {
        $s = Seedbed::create(['name' => $name, 'status' => $status]);
        $s->programs()->attach($this->program->id);

        return $s;
    }

    private function request(User $u, Seedbed $s, string $status): MembershipRequest
    {
        return MembershipRequest::create([
            'user_id' => $u->id, 'seedbed_id' => $s->id, 'program_id' => $this->program->id, 'status' => $status,
            'phone' => '3001234567', 'message' => 'Quiero unirme al semillero por favor.',
        ]);
    }

    private function member(Seedbed $s, User $u, string $status = 'ACTIVO', bool $linked = true): SeedbedMember
    {
        return SeedbedMember::create([
            'seedbed_id' => $s->id, 'user_id' => $linked ? $u->id : null, 'name' => 'Integrante', 'student_code' => 'C' . uniqid(),
            'program_id' => $this->program->id, 'level' => 'PR', 'email' => strtoupper($u->email), 'status' => $status,
        ]);
    }

    private function eligibility(User $u, Seedbed $s): array
    {
        Sanctum::actingAs($u);

        return $this->getJson('/api/requests/eligibility?seedbed_id=' . $s->id)->assertOk()->json();
    }

    private function apply(User $u, Seedbed $s)
    {
        Sanctum::actingAs($u);

        return $this->postJson('/api/requests', [
            'seedbed_id' => $s->id, 'program_id' => $this->program->id, 'phone' => '3001234567',
            'message' => 'Quiero unirme porque me interesa la investigación.',
        ]);
    }

    public function test_a_student_without_any_seedbed_can_apply(): void
    {
        $u = $this->student(); $s = $this->seedbed('A');

        $this->assertSame(['can_apply' => true, 'state' => 'free', 'message' => null, 'seedbed_name' => null], $this->eligibility($u, $s));
        $this->apply($u, $s)->assertStatus(201);
    }

    public function test_a_pending_request_blocks_applying_anywhere_and_names_the_seedbed(): void
    {
        $u = $this->student(); $a = $this->seedbed('Semillero A'); $b = $this->seedbed('Semillero B');
        $this->request($u, $a, 'PENDIENTE');

        $here = $this->eligibility($u, $a); $other = $this->eligibility($u, $b);

        $this->assertSame(['pending_here', false], [$here['state'], $here['can_apply']]);
        $this->assertSame('pending_other', $other['state']);
        $this->assertStringContainsString('Semillero A', $other['message']);
        $this->apply($u, $b)->assertStatus(409)->assertJsonPath('state', 'pending_other');
    }

    public function test_an_approved_request_not_yet_registered_as_member_still_blocks(): void
    {
        $u = $this->student(); $a = $this->seedbed('A'); $b = $this->seedbed('B');
        $this->request($u, $a, 'APROBADA');

        $this->assertSame('approved_here', $this->eligibility($u, $a)['state']);
        $this->assertSame('approved_other', $this->eligibility($u, $b)['state']);
        $this->apply($u, $b)->assertStatus(409);
    }

    public function test_an_active_member_is_blocked_whether_linked_by_user_or_only_by_email(): void
    {
        $linked = $this->student(); $byEmail = $this->student();
        $a = $this->seedbed('A'); $b = $this->seedbed('B');
        $this->request($linked, $a, 'APROBADA');  $this->member($a, $linked, 'ACTIVO', true);
        $this->request($byEmail, $a, 'APROBADA'); $this->member($a, $byEmail, 'ACTIVO', false);   // el líder no lo vinculó: se reconoce por correo

        foreach ([$linked, $byEmail] as $u) {
            $this->assertSame('member_here', $this->eligibility($u, $a)['state']);
            $this->assertSame('member_other', $this->eligibility($u, $b)['state']);
            $this->apply($u, $b)->assertStatus(409)->assertJsonPath('state', 'member_other');
        }
    }

    public function test_a_member_registered_directly_by_the_leader_without_a_request_is_also_blocked(): void
    {
        $u = $this->student(); $a = $this->seedbed('A'); $b = $this->seedbed('B');
        $this->member($a, $u);

        $this->apply($u, $b)->assertStatus(409);
    }

    public function test_leaving_the_seedbed_frees_the_student_even_with_an_old_approved_request(): void
    {
        $u = $this->student(); $a = $this->seedbed('A'); $b = $this->seedbed('B');
        $this->request($u, $a, 'APROBADA');
        $m = $this->member($a, $u);
        $this->assertSame('member_other', $this->eligibility($u, $b)['state']);

        $m->forceFill(['status' => 'INACTIVO'])->save();      // el líder lo inactiva: sale del semillero

        $this->assertTrue($this->eligibility($u, $b)['can_apply']);
        $this->apply($u, $b)->assertStatus(201);
    }

    public function test_an_inactive_seedbed_does_not_hold_the_student(): void
    {
        $u = $this->student(); $a = $this->seedbed('A'); $b = $this->seedbed('B');
        $this->request($u, $a, 'APROBADA'); $this->member($a, $u);
        $a->forceFill(['status' => 'INACTIVO'])->save();

        $this->apply($u, $b)->assertStatus(201);
    }

    public function test_a_rejected_request_does_not_block(): void
    {
        $u = $this->student(); $a = $this->seedbed('A'); $b = $this->seedbed('B');
        $this->request($u, $a, 'RECHAZADA');

        $this->apply($u, $b)->assertStatus(201);
    }

    public function test_the_block_is_per_student_not_global(): void
    {
        $busy = $this->student(); $free = $this->student(); $a = $this->seedbed('A');
        $this->request($busy, $a, 'PENDIENTE');

        $this->apply($free, $a)->assertStatus(201);
    }

    public function test_only_students_can_ask_for_eligibility(): void
    {
        $s = $this->seedbed('A');
        $this->getJson('/api/requests/eligibility')->assertStatus(401);
        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO', 'ADMIN_SISTEMA'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role, 'status' => 'ACTIVO']));
            $this->getJson('/api/requests/eligibility?seedbed_id=' . $s->id)->assertStatus(403);
        }
    }
}
