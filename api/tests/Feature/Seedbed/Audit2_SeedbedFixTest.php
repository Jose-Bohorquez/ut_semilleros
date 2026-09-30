<?php

namespace Tests\Feature\Seedbed;

use Tests\TestCase;
use App\Models\Area;
use App\Models\Audit;
use App\Models\Coordinator;
use App\Models\Faculty;
use App\Models\MembershipRequest;
use App\Models\Objective;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\SeedbedMember;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Auditoría 2 (CU13-CU21): regresiones de T1 (fuga a ESTUDIANTE), CU15-H1,
 * CU14-H1 (reasignar líder), CU16-H2/T2 (integrantes reales), CU21-H1 y
 * CU15-H1b/CU24-H1 (auditoría del rechazo automático).
 */
class Audit2_SeedbedFixTest extends TestCase
{
    use RefreshDatabase;

    private function program(): Program
    {
        $faculty = Faculty::create(['name' => 'Facultad ' . uniqid(), 'status' => 'ACTIVO']);
        return Program::create(['name' => 'Programa ' . uniqid(), 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
    }

    private function area(): Area
    {
        return Area::create(['name' => 'Área ' . uniqid(), 'code' => 'AT-' . uniqid(), 'status' => 'ACTIVO']);
    }

    private function makeSeedbed(array $overrides = []): Seedbed
    {
        $coordinator = Coordinator::create([
            'name' => 'Coord Test', 'document' => uniqid(), 'email' => uniqid() . '@ut.edu.co',
            'phone' => '3001234567', 'status' => 'ACTIVO',
        ]);
        $seedbed = Seedbed::create(array_merge([
            'code' => 'SB-' . uniqid(),
            'name' => 'Semillero',
            'objetivo_general' => 'x objetivo largo',
            'authorization_reference' => 'Acta 1',
            'inactivation_reason' => null,
            'coordinator_id' => $coordinator->id,
            'status' => 'ACTIVO',
        ], $overrides));
        $seedbed->programs()->attach($this->program()->id);
        $seedbed->areas()->attach($this->area()->id);
        return $seedbed;
    }

    private function leaderOf(Seedbed $seedbed): User
    {
        $leader = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO']);
        $seedbed->users()->attach($leader->id, ['role' => 'LIDER']);
        return $leader;
    }

    private function payloadFor(Seedbed $seedbed, array $overrides = []): array
    {
        return array_merge([
            'code' => $seedbed->code,
            'name' => $seedbed->name,
            'programs' => $seedbed->programs()->pluck('programs.id')->all(),
            'areas' => $seedbed->areas()->pluck('areas.id')->all(),
            'objetivo_general' => 'Fomentar la investigación aplicada',
            'authorization_reference' => 'Acta 1',
        ], $overrides);
    }

    /* ---------- 1. T1: ESTUDIANTE ---------- */

    public function test_student_list_hides_inactive_seedbeds_and_sensitive_fields(): void
    {
        $active = $this->makeSeedbed(['name' => 'Activo']);
        $this->leaderOf($active);
        $this->makeSeedbed(['name' => 'Oculto', 'status' => 'INACTIVO', 'inactivation_reason' => 'motivo interno']);

        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $res = $this->getJson('/api/seedbeds')->assertOk();

        $this->assertCount(1, $res->json('seedbeds'));
        $this->assertSame('Activo', $res->json('seedbeds.0.name'));
        $raw = $res->getContent();
        foreach (['authorization_reference', 'inactivation_reason', 'pending_requests_count', '"users"', '3001234567', '"document"', '"phone"'] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $raw, "Fuga: {$forbidden}");
        }
        // Lo que la PWA necesita sigue presente.
        $this->assertSame('Coord Test', $res->json('seedbeds.0.coordinator.name'));
        $this->assertStringEndsWith('@ut.edu.co', $res->json('seedbeds.0.coordinator.email'));
        $this->assertNotEmpty($res->json('seedbeds.0.faculty_names'));
        $this->assertNotEmpty($res->json('seedbeds.0.leader_name'));
    }

    public function test_student_show_inactive_seedbed_is_404_with_message(): void
    {
        $inactive = $this->makeSeedbed(['status' => 'INACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $this->getJson("/api/seedbeds/{$inactive->id}")
            ->assertStatus(404)
            ->assertJsonPath('message', 'Este semillero ya no está disponible');
    }

    public function test_student_show_active_seedbed_has_no_pii(): void
    {
        $sb = $this->makeSeedbed();
        $leader = $this->leaderOf($sb);
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $res = $this->getJson("/api/seedbeds/{$sb->id}")->assertOk();
        $raw = $res->getContent();
        foreach (['authorization_reference', 'inactivation_reason', 'pending_requests_count', '"users"', '3001234567', '"document"', $leader->email] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $raw);
        }
        $this->assertArrayNotHasKey('active_members', $res->json('seedbed'));
    }

    public function test_leader_and_admin_still_see_everything(): void
    {
        $sb = $this->makeSeedbed(['status' => 'INACTIVO', 'inactivation_reason' => 'motivo interno']);
        $leader = $this->leaderOf($sb);

        foreach ([User::factory()->create(['role' => 'ADMIN_SISTEMA']), $leader] as $u) {
            Sanctum::actingAs($u);
            $res = $this->getJson('/api/seedbeds')->assertOk();
            $this->assertSame('INACTIVO', $res->json('seedbeds.0.status'));
            $this->assertSame('Acta 1', $res->json('seedbeds.0.authorization_reference'));
            $this->assertSame('motivo interno', $res->json('seedbeds.0.inactivation_reason'));
            $this->getJson("/api/seedbeds/{$sb->id}")->assertOk()->assertJsonPath('seedbed.status', 'INACTIVO');
        }
    }

    public function test_student_only_sees_active_objectives_of_active_seedbeds(): void
    {
        $active = $this->makeSeedbed(['name' => 'A']);
        $inactive = $this->makeSeedbed(['name' => 'B', 'status' => 'INACTIVO']);
        Objective::create(['seedbed_id' => $active->id, 'content' => 'Objetivo activo largo', 'order' => 0, 'status' => 'ACTIVO']);
        Objective::create(['seedbed_id' => $active->id, 'content' => 'Objetivo inactivo largo', 'order' => 1, 'status' => 'INACTIVO']);
        Objective::create(['seedbed_id' => $inactive->id, 'content' => 'Objetivo de semillero inactivo', 'order' => 0, 'status' => 'ACTIVO']);

        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $res = $this->getJson('/api/objectives')->assertOk();
        $this->assertCount(1, $res->json('objectives'));
        $this->assertSame('Objetivo activo largo', $res->json('objectives.0.content'));

        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->assertCount(3, $this->getJson('/api/objectives')->json('objectives'));
    }

    /* ---------- 2. CU15-H1 ---------- */

    public function test_update_cannot_change_status(): void
    {
        $sb = $this->makeSeedbed();
        $leader = $this->leaderOf($sb);
        $req = MembershipRequest::create(['user_id' => User::factory()->create(['role' => 'ESTUDIANTE'])->id,
            'seedbed_id' => $sb->id, 'status' => 'PENDIENTE']);
        Sanctum::actingAs($leader);

        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['status' => 'INACTIVO']))
            ->assertStatus(422)
            ->assertJsonValidationErrors(['status'])
            ->assertJsonPath('errors.status.0', 'Para cambiar el estado usa Activar o Inactivar.');

        $this->assertDatabaseHas('seedbeds', ['id' => $sb->id, 'status' => 'ACTIVO']);
        $this->assertDatabaseHas('requests', ['id' => $req->id, 'status' => 'PENDIENTE']);

        // Mandar el mismo estado (o ninguno) sigue funcionando.
        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['status' => 'ACTIVO']))->assertOk();
        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb))->assertOk();
    }

    /* ---------- 3. CU14-H1 ---------- */

    public function test_admin_can_assign_and_reassign_leader(): void
    {
        $sb = $this->makeSeedbed();
        $old = $this->leaderOf($sb);
        $new = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));

        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['leader_id' => $new->id]))->assertOk();

        $this->assertDatabaseHas('seedbed_user', ['seedbed_id' => $sb->id, 'user_id' => $new->id, 'role' => 'LIDER']);
        $this->assertDatabaseMissing('seedbed_user', ['seedbed_id' => $sb->id, 'user_id' => $old->id]);
        $this->assertSame(1, $sb->users()->wherePivot('role', 'LIDER')->count());
    }

    public function test_admin_can_set_leader_on_create(): void
    {
        $leader = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $res = $this->postJson('/api/seedbeds', [
            'code' => 'SB-ADM', 'name' => 'Creado por admin',
            'programs' => [$this->program()->id], 'areas' => [$this->area()->id],
            'objetivo_general' => 'Fomentar la investigación', 'authorization_reference' => 'Acta 2', 'status' => 'ACTIVO',
            'leader_id' => $leader->id,
        ])->assertCreated();
        $this->assertDatabaseHas('seedbed_user', [
            'seedbed_id' => $res->json('seedbed.id'), 'user_id' => $leader->id, 'role' => 'LIDER',
        ]);
    }

    public function test_leader_id_must_be_active_leader_user(): void
    {
        $sb = $this->makeSeedbed();
        $student = User::factory()->create(['role' => 'ESTUDIANTE', 'status' => 'ACTIVO']);
        $inactive = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'status' => 'INACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        foreach ([$student->id, $inactive->id, 99999] as $id) {
            $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['leader_id' => $id]))
                ->assertStatus(422)->assertJsonValidationErrors(['leader_id']);
        }
    }

    public function test_leader_cannot_reassign_leader(): void
    {
        $sb = $this->makeSeedbed();
        $old = $this->leaderOf($sb);
        $other = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO']);
        Sanctum::actingAs($old);
        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['leader_id' => $other->id]))
            ->assertStatus(403);
        $this->assertDatabaseHas('seedbed_user', ['seedbed_id' => $sb->id, 'user_id' => $old->id, 'role' => 'LIDER']);
    }

    public function test_new_leader_can_edit_and_old_leader_loses_access(): void
    {
        $sb = $this->makeSeedbed();
        $old = $this->leaderOf($sb);
        $new = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['leader_id' => $new->id]))->assertOk();

        Sanctum::actingAs($new);
        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['name' => 'Editado']))->assertOk();
        Sanctum::actingAs($old);
        $this->putJson("/api/seedbeds/{$sb->id}", $this->payloadFor($sb, ['name' => 'Otro']))->assertStatus(403);
    }

    /* ---------- 4. CU16-H2 / T2 ---------- */

    public function test_members_count_counts_active_seedbed_members_not_leaders(): void
    {
        $sb = $this->makeSeedbed();
        $this->leaderOf($sb);
        $program = $sb->programs()->first();
        foreach (['A' => 'ACTIVO', 'B' => 'ACTIVO', 'C' => 'INACTIVO'] as $code => $status) {
            SeedbedMember::create(['seedbed_id' => $sb->id, 'name' => "M{$code}", 'student_code' => $code,
                'program_id' => $program->id, 'level' => 'PR', 'email' => "{$code}@ut.edu.co", 'status' => $status]);
        }
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->assertSame(2, $this->getJson('/api/seedbeds')->json('seedbeds.0.members_count'));
        $this->assertSame(2, $this->getJson("/api/seedbeds/{$sb->id}")->json('seedbed.members_count'));
    }

    public function test_show_lists_active_members_without_contact_data(): void
    {
        $sb = $this->makeSeedbed();
        $program = $sb->programs()->first();
        SeedbedMember::create(['seedbed_id' => $sb->id, 'name' => 'Ana Activa', 'student_code' => 'A1',
            'program_id' => $program->id, 'level' => 'PG', 'email' => 'ana@ut.edu.co', 'phone' => '3110000000', 'status' => 'ACTIVO']);
        SeedbedMember::create(['seedbed_id' => $sb->id, 'name' => 'Ines Inactiva', 'student_code' => 'A2',
            'program_id' => $program->id, 'level' => 'PR', 'email' => 'ines@ut.edu.co', 'status' => 'INACTIVO']);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $res = $this->getJson("/api/seedbeds/{$sb->id}")->assertOk();
        $members = $res->json('seedbed.active_members');
        $this->assertCount(1, $members);
        $this->assertSame('Ana Activa', $members[0]['name']);
        $this->assertSame('PG', $members[0]['level']);
        $this->assertSame($program->name, $members[0]['program_name']);
        $this->assertStringNotContainsString('ana@ut.edu.co', json_encode($members));
        $this->assertStringNotContainsString('3110000000', json_encode($members));
    }

    /* ---------- 6. CU21-H1 ---------- */

    public function test_reactivating_member_with_code_already_active_is_rejected(): void
    {
        $sb = $this->makeSeedbed();
        $leader = $this->leaderOf($sb);
        $program = $sb->programs()->first();
        $old = SeedbedMember::create(['seedbed_id' => $sb->id, 'name' => 'Viejo', 'student_code' => 'C1',
            'program_id' => $program->id, 'level' => 'PR', 'email' => 'v@ut.edu.co', 'status' => 'INACTIVO']);
        SeedbedMember::create(['seedbed_id' => $sb->id, 'name' => 'Nuevo', 'student_code' => 'C1',
            'program_id' => $program->id, 'level' => 'PR', 'email' => 'n@ut.edu.co', 'status' => 'ACTIVO']);
        Sanctum::actingAs($leader);
        $this->putJson("/api/seedbeds/{$sb->id}/members/{$old->id}/toggle-status")
            ->assertStatus(422)
            ->assertJsonPath('errors.student_code.0', 'El estudiante ya es integrante de este semillero.');
        $this->assertDatabaseHas('seedbed_members', ['id' => $old->id, 'status' => 'INACTIVO']);
    }

    /* ---------- 7. CU15-H1b / CU24-H1 ---------- */

    public function test_inactivating_seedbed_audits_each_auto_rejected_request(): void
    {
        $sb = $this->makeSeedbed();
        $leader = $this->leaderOf($sb);
        $ids = [];
        foreach (range(1, 2) as $i) {
            $ids[] = MembershipRequest::create(['user_id' => User::factory()->create(['role' => 'ESTUDIANTE'])->id,
                'seedbed_id' => $sb->id, 'status' => 'PENDIENTE'])->id;
        }
        Audit::query()->delete();
        Sanctum::actingAs($leader);
        $this->putJson("/api/seedbeds/{$sb->id}/toggle-status", ['reason' => 'Cierre por vacaciones'])
            ->assertOk()->assertJsonPath('rejected_requests_count', 2);

        foreach ($ids as $id) {
            $this->assertDatabaseHas('requests', ['id' => $id, 'status' => 'RECHAZADA', 'reason' => 'Semillero inactivo', 'reviewed_by' => $leader->id]);
            $this->assertNotNull(MembershipRequest::find($id)->reviewed_at);
        }
        $this->assertSame(2, Audit::where('table_name', 'requests')->where('action', 'UPDATE')->count());
    }
}
