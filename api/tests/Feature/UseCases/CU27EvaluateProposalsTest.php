<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\Area;
use App\Models\Audit;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Proposal;
use App\Models\Seedbed;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU27 — Evaluar propuestas (RF11, RN01, RN07; RNF03, RNF12). Cada test cita el paso, alterno o
 * excepción de la especificación que demuestra.
 */
class CU27EvaluateProposalsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, array $extra = []): User
    {
        return User::factory()->create(array_merge(['role' => $role, 'status' => 'ACTIVO'], $extra));
    }

    private function program(): Program
    {
        $faculty = Faculty::create(['name' => 'Facultad ' . uniqid(), 'status' => 'ACTIVO']);
        return Program::create(['name' => 'Programa ' . uniqid(), 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
    }

    private function area(string $name = 'Área'): Area
    {
        return Area::create(['name' => $name . ' ' . uniqid(), 'code' => 'A-' . uniqid(), 'status' => 'ACTIVO']);
    }

    private function proposal(array $areas, array $attrs = [], ?User $student = null, ?Program $program = null): Proposal
    {
        $student ??= $this->user('ESTUDIANTE', ['phone' => '3207776655']);
        $p = Proposal::create(array_merge([
            'user_id'     => $student->id,
            'program_id'  => ($program ?? $this->program())->id,
            'title'       => 'Idea ' . uniqid(),
            'description' => 'Descripción completa de la propuesta para evaluar en CU27, con suficiente detalle.',
            'phone'       => '3001234567',
            'status'      => 'RECIBIDA',
        ], array_diff_key($attrs, ['review_note' => 1])));
        // review_note no es asignable en masa (solo lo fija la evaluación): se fija aparte.
        if (isset($attrs['review_note'])) {
            $p->forceFill(['review_note' => $attrs['review_note']])->save();
        }
        $p->areas()->attach(array_map(fn ($a) => $a->id, $areas));
        return $p;
    }

    /** Líder de un semillero que cubre las áreas dadas. */
    private function leaderOfAreas(array $areas): User
    {
        $leader  = $this->user('LIDER_SEMILLERO');
        $seedbed = Seedbed::create(['name' => 'Semillero ' . uniqid(), 'status' => 'ACTIVO']);
        $seedbed->areas()->attach(array_map(fn ($a) => $a->id, $areas));
        $seedbed->users()->attach($leader->id, ['role' => 'LIDER']);
        return $leader;
    }

    private function ids($response): array
    {
        return array_column($response->json('proposals'), 'id');
    }

    /* ── Acceso ── */

    public function test_unauthenticated_gets_401(): void
    {
        $this->getJson('/api/proposals')->assertStatus(401);
        $this->putJson('/api/proposals/1/update-status', [])->assertStatus(401);
    }

    public function test_students_cannot_list_view_or_evaluate_proposals(): void
    {
        $p = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ESTUDIANTE'));

        $this->getJson('/api/proposals')->assertStatus(403);
        $this->getJson("/api/proposals/{$p->id}")->assertStatus(403);
        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'VIABLE'])->assertStatus(403);
    }

    /* ── Paso 2: listado con fecha, estudiante, programa, áreas y estado ── */

    public function test_administrativo_sees_every_proposal_with_the_columns_of_the_spec(): void
    {
        $a1 = $this->area(); $a2 = $this->area();
        $p1 = $this->proposal([$a1]);
        $p2 = $this->proposal([$a2]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $res = $this->getJson('/api/proposals')->assertOk();
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $this->ids($res));

        $row = collect($res->json('proposals'))->firstWhere('id', $p1->id);
        foreach (['created_at', 'student', 'program', 'areas', 'status', 'status_label', 'title', 'excerpt'] as $key) {
            $this->assertArrayHasKey($key, $row);
        }
        $this->assertSame('Recibida', $row['status_label']);
        $this->assertNotEmpty($row['student']['name']);
    }

    /** RNF12: el listado no lleva datos de contacto ni la descripción completa. */
    public function test_the_list_never_carries_contact_data(): void
    {
        $student = $this->user('ESTUDIANTE', ['phone' => '3207776655']);
        $this->proposal([$this->area()], ['phone' => '3105559911'], $student);

        foreach (['ADMINISTRATIVO', 'ADMIN_SISTEMA'] as $role) {
            Sanctum::actingAs($this->user($role));
            $json = json_encode($this->getJson('/api/proposals')->assertOk()->json());
            $this->assertStringNotContainsString('3105559911', $json, $role);
            $this->assertStringNotContainsString('3207776655', $json, $role);
            $this->assertStringNotContainsString($student->email, $json, $role);
            $this->assertStringNotContainsString('"contact"', $json, $role);
            $this->assertStringNotContainsString('"description"', $json, $role);
        }
    }

    public function test_admin_del_sistema_can_list_but_not_evaluate(): void
    {
        $p = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->assertContains($p->id, $this->ids($this->getJson('/api/proposals')->assertOk()));
        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'VIABLE'])->assertStatus(403);
    }

    /* ── A1: el Líder consulta las propuestas de las áreas de sus semilleros, sin cambiar su estado ── */

    public function test_a1_leader_only_sees_proposals_of_the_areas_of_his_seedbeds(): void
    {
        $mine = $this->area('Mía'); $other = $this->area('Ajena');
        $pMine  = $this->proposal([$mine]);
        $pOther = $this->proposal([$other]);
        $pBoth  = $this->proposal([$mine, $other]);
        Sanctum::actingAs($this->leaderOfAreas([$mine]));

        $ids = $this->ids($this->getJson('/api/proposals')->assertOk());

        $this->assertEqualsCanonicalizing([$pMine->id, $pBoth->id], $ids);
        $this->assertNotContains($pOther->id, $ids);
    }

    public function test_a1_a_leader_without_seedbeds_sees_nothing(): void
    {
        $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('LIDER_SEMILLERO'));

        $res = $this->getJson('/api/proposals')->assertOk();
        $this->assertSame([], $res->json('proposals'));
        $this->assertSame('No hay propuestas para los filtros seleccionados', $res->json('message'));
    }

    public function test_a1_a_seedbed_led_by_someone_else_does_not_count(): void
    {
        $area = $this->area();
        $p = $this->proposal([$area]);
        $seedbed = Seedbed::create(['name' => 'Ajeno', 'status' => 'ACTIVO']);
        $seedbed->areas()->attach($area->id);
        $seedbed->users()->attach($this->user('LIDER_SEMILLERO')->id, ['role' => 'LIDER']);
        Sanctum::actingAs($this->user('LIDER_SEMILLERO'));

        $this->assertNotContains($p->id, $this->ids($this->getJson('/api/proposals')->assertOk()));
    }

    public function test_a1_the_leader_cannot_change_the_state(): void
    {
        $area = $this->area();
        $p = $this->proposal([$area]);
        Sanctum::actingAs($this->leaderOfAreas([$area]));

        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'VIABLE'])->assertStatus(403);   // E2
        $this->assertSame('RECIBIDA', $p->fresh()->status);
    }

    /* ── Paso 3: filtros por área, programa, estado y fechas ── */

    public function test_filters_by_area_program_and_state_and_they_combine(): void
    {
        $a1 = $this->area(); $a2 = $this->area();
        $prog1 = $this->program(); $prog2 = $this->program();
        $p1 = $this->proposal([$a1], [], null, $prog1);
        $p2 = $this->proposal([$a2], [], null, $prog1);
        $p3 = $this->proposal([$a1], ['status' => 'VIABLE'], null, $prog2);
        $p4 = $this->proposal([$a1], ['status' => 'ARCHIVADA', 'review_note' => 'No es viable por alcance.'], null, $prog1);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $ids = fn (string $q) => $this->ids($this->getJson("/api/proposals?$q")->assertOk());

        $this->assertEqualsCanonicalizing([$p1->id, $p3->id, $p4->id], $ids("area_id={$a1->id}"));
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id, $p4->id], $ids("program_id={$prog1->id}"));
        $this->assertSame([$p3->id], $ids('status=VIABLE'));
        $this->assertSame([$p4->id], $ids('status=ARCHIVADA'));
        $this->assertEqualsCanonicalizing([$p1->id, $p2->id], $ids('status=RECIBIDA'));
        $this->assertSame([$p4->id], $ids("area_id={$a1->id}&program_id={$prog1->id}&status=ARCHIVADA"));
    }

    public function test_the_internal_state_names_also_work_as_filters(): void
    {
        $p = $this->proposal([$this->area()], ['status' => 'VIABLE']);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->assertSame([$p->id], $this->ids($this->getJson('/api/proposals?status=VIABLE')->assertOk()));
    }

    /** Días completos de Bogotá (UTC-5). */
    public function test_date_range_uses_whole_days_in_bogota_time(): void
    {
        $area = $this->area();
        $before = $this->proposal([$area]); $before->forceFill(['created_at' => '2026-03-10 04:59:59'])->save();
        $start  = $this->proposal([$area]); $start->forceFill(['created_at' => '2026-03-10 05:00:00'])->save();
        $end    = $this->proposal([$area]); $end->forceFill(['created_at' => '2026-03-11 04:59:59'])->save();
        $after  = $this->proposal([$area]); $after->forceFill(['created_at' => '2026-03-11 05:00:00'])->save();
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $ids = $this->ids($this->getJson('/api/proposals?from=2026-03-10&to=2026-03-10')->assertOk());

        $this->assertEqualsCanonicalizing([$start->id, $end->id], $ids);
    }

    public function test_invalid_filters_are_rejected_and_an_empty_result_has_a_message(): void
    {
        $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->getJson('/api/proposals?status=XYZ')->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->getJson('/api/proposals?from=2026-03-10&to=2026-03-01')->assertStatus(422)->assertJsonValidationErrors(['to']);
        $this->assertSame('No hay propuestas para los filtros seleccionados',
            $this->getJson('/api/proposals?area_id=999999')->assertOk()->json('message'));
    }

    /* ── Paso 4: descripción completa y datos de contacto (solo el Administrativo) ── */

    public function test_step4_administrativo_gets_the_full_description_and_the_contact_data(): void
    {
        $student = $this->user('ESTUDIANTE', ['phone' => '3207776655']);
        $p = $this->proposal([$this->area()], ['phone' => '3105559911'], $student);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $row = $this->getJson("/api/proposals/{$p->id}")->assertOk()->json('proposal');

        $this->assertSame($p->description, $row['description']);
        $this->assertSame($student->email, $row['contact']['email']);
        $this->assertSame('3105559911', $row['contact']['phone']);
        $this->assertSame($student->name, $row['student']['name']);
    }

    public function test_step4_the_contact_falls_back_to_the_student_phone(): void
    {
        $student = $this->user('ESTUDIANTE', ['phone' => '3207776655']);
        $p = $this->proposal([$this->area()], ['phone' => null], $student);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->assertSame('3207776655', $this->getJson("/api/proposals/{$p->id}")->json('proposal.contact.phone'));
    }

    /** RNF12: el Líder (A1) y el Administrador del sistema consultan sin datos de contacto. */
    public function test_step4_leader_and_admin_do_not_receive_contact_data(): void
    {
        $area = $this->area();
        $p = $this->proposal([$area], ['phone' => '3105559911']);

        foreach ([$this->leaderOfAreas([$area]), $this->user('ADMIN_SISTEMA')] as $actor) {
            Sanctum::actingAs($actor);
            $res = $this->getJson("/api/proposals/{$p->id}")->assertOk();
            $this->assertSame($p->description, $res->json('proposal.description'));
            $this->assertArrayNotHasKey('contact', $res->json('proposal'));
            $this->assertStringNotContainsString('3105559911', json_encode($res->json()));
        }
    }

    public function test_a1_a_leader_cannot_open_a_proposal_outside_his_areas(): void
    {
        $p = $this->proposal([$this->area('Ajena')]);
        Sanctum::actingAs($this->leaderOfAreas([$this->area('Mía')]));

        $this->getJson("/api/proposals/{$p->id}")->assertStatus(403);
    }

    public function test_show_returns_404_for_a_missing_proposal(): void
    {
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));
        $this->getJson('/api/proposals/999999')->assertStatus(404);
    }

    /* ── Pasos 5-6: Marcar viable / Archivar con una observación ── */

    public function test_mark_viable_with_a_note_saves_state_note_reviewer_and_date(): void
    {
        $p = $this->proposal([$this->area()]);
        $evaluator = $this->user('ADMINISTRATIVO');
        Sanctum::actingAs($evaluator);

        $res = $this->putJson("/api/proposals/{$p->id}/update-status", [
            'status' => 'VIABLE', 'review_note' => 'Encaja con el semillero de IA, coordina una reunión.',
        ])->assertOk();

        $this->assertSame('Propuesta marcada como viable', $res->json('message'));
        $p->refresh();
        $this->assertSame('VIABLE', $p->status);               // Viable (puente de estados)
        $this->assertSame('Viable', $p->status_label);
        $this->assertSame('Encaja con el semillero de IA, coordina una reunión.', $p->review_note);
        $this->assertSame($evaluator->id, (int) $p->reviewed_by);
        $this->assertNotNull($p->reviewed_at);
    }

    public function test_mark_viable_does_not_require_a_note(): void
    {
        $p = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'VIABLE'])->assertOk();

        $this->assertSame('VIABLE', $p->fresh()->status);
        $this->assertNull($p->fresh()->review_note);
    }

    public function test_archive_with_a_note_works(): void
    {
        $p = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $res = $this->putJson("/api/proposals/{$p->id}/update-status", [
            'status' => 'ARCHIVADA', 'review_note' => 'Fuera del alcance de los semilleros actuales.',
        ])->assertOk();

        $this->assertSame('Propuesta archivada', $res->json('message'));
        $this->assertSame('ARCHIVADA', $p->fresh()->status);
        $this->assertSame('Archivada', $p->fresh()->status_label);
    }

    /** E1 / RF11: «Archivar exige observación» — no permite confirmar. */
    public function test_e1_archiving_without_a_note_is_blocked_and_the_state_is_kept(): void
    {
        $p = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        foreach ([[], ['review_note' => ''], ['review_note' => '   '], ['review_note' => 'no']] as $payload) {
            $res = $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'ARCHIVADA'] + $payload)
                ->assertStatus(422)->assertJsonValidationErrors(['review_note']);
        }
        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'ARCHIVADA'])
            ->assertJsonPath('errors.review_note.0', 'La observación es obligatoria para archivar la propuesta.');

        $p->refresh();
        $this->assertSame('RECIBIDA', $p->status);
        $this->assertNull($p->reviewed_by);
    }

    public function test_the_internal_names_are_accepted_too(): void
    {
        $a = $this->proposal([$this->area()]);
        $b = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->putJson("/api/proposals/{$a->id}/update-status", ['status' => 'VIABLE'])->assertOk();
        $this->putJson("/api/proposals/{$b->id}/update-status", ['status' => 'ARCHIVADA', 'review_note' => 'Sin viabilidad técnica.'])->assertOk();

        $this->assertSame('VIABLE', $a->fresh()->status);
        $this->assertSame('ARCHIVADA', $b->fresh()->status);
    }

    public function test_an_evaluation_must_be_viable_or_archived(): void
    {
        $p = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        foreach (['PENDIENTE', 'RECIBIDA', 'XYZ', ''] as $status) {
            $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => $status])
                ->assertStatus(422)->assertJsonValidationErrors(['status']);
        }
        $this->assertSame('RECIBIDA', $p->fresh()->status);
    }

    public function test_the_observation_has_a_maximum_length(): void
    {
        $p = $this->proposal([$this->area()]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'VIABLE', 'review_note' => str_repeat('a', 1001)])
            ->assertStatus(422)->assertJsonValidationErrors(['review_note']);
    }

    /** Postcondición de falla: «la propuesta conserva su estado»; la spec no define la reevaluación. */
    public function test_an_evaluated_proposal_is_not_evaluated_again(): void
    {
        $p = $this->proposal([$this->area()], ['status' => 'VIABLE', 'review_note' => 'Viable desde el inicio.']);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'ARCHIVADA', 'review_note' => 'Cambié de opinión.'])
            ->assertStatus(409)->assertJsonPath('message', 'Esta propuesta ya fue evaluada.');

        $this->assertSame('VIABLE', $p->fresh()->status);
        $this->assertSame('Viable desde el inicio.', $p->fresh()->review_note);
    }

    public function test_evaluating_a_missing_proposal_is_404(): void
    {
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));
        $this->putJson('/api/proposals/999999/update-status', ['status' => 'VIABLE'])->assertStatus(404);
    }

    /** E2: quien no es Administrativo recibe 403 y la propuesta no cambia. */
    public function test_e2_every_other_role_gets_403_and_the_state_is_kept(): void
    {
        $area = $this->area();
        $p = $this->proposal([$area]);
        $actors = [$this->user('ADMIN_SISTEMA'), $this->leaderOfAreas([$area]), $this->user('ESTUDIANTE')];

        foreach ($actors as $actor) {
            Sanctum::actingAs($actor);
            $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'VIABLE'])->assertStatus(403);
        }
        $this->assertSame('RECIBIDA', $p->fresh()->status);
    }

    /* ── Paso 6: auditoría (CU29) ── */

    public function test_the_evaluation_is_audited_with_the_old_and_new_values(): void
    {
        $p = $this->proposal([$this->area()]);
        $evaluator = $this->user('ADMINISTRATIVO');
        Sanctum::actingAs($evaluator);

        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'VIABLE', 'review_note' => 'Aprobada para ejecución.'])->assertOk();

        $row = Audit::where('table_name', 'proposals')->where('record_id', $p->id)->where('action', 'STATUS_CHANGE')->latest('id')->first();
        $this->assertNotNull($row);
        $this->assertSame($evaluator->id, (int) $row->user_id);
        $this->assertSame('RECIBIDA', $row->old_values['status']);
        $this->assertSame('VIABLE', $row->new_values['status']);
        $this->assertSame('Aprobada para ejecución.', $row->new_values['review_note']);
    }

    /* ── Paso 7: el estudiante ve el nuevo estado en CU26 ── */

    public function test_step7_the_student_sees_the_result_in_my_proposals(): void
    {
        $student = $this->user('ESTUDIANTE');
        $p = $this->proposal([$this->area()], [], $student);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));
        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'ARCHIVADA', 'review_note' => 'Duplica un semillero existente.'])->assertOk();

        Sanctum::actingAs($student);
        $mine = $this->getJson('/api/proposals/my')->assertOk()->json('proposals.0');

        $this->assertSame('Archivada', $mine['status_label']);
        $this->assertSame('Duplica un semillero existente.', $mine['review_note']);
    }

    /* ── El resto de rutas ya no permiten saltarse la evaluación (CU25-H4) ── */

    public function test_staff_cannot_edit_or_create_proposals_any_more(): void
    {
        $area = $this->area();
        $p = $this->proposal([$area]);
        $payload = [
            'program_id' => $p->program_id, 'areas' => [$area->id], 'title' => 'Cambiada',
            'description' => 'Descripción nueva con más de veinte caracteres.', 'status' => 'VIABLE',
        ];

        foreach (['ADMIN_SISTEMA', 'ADMINISTRATIVO', 'LIDER_SEMILLERO'] as $role) {
            Sanctum::actingAs($this->user($role));
            $this->putJson("/api/proposals/{$p->id}", $payload)->assertStatus(403);
            $this->postJson('/api/proposals', $payload)->assertStatus(403);
        }
        $this->assertSame('RECIBIDA', $p->fresh()->status);
        $this->assertNotSame('Cambiada', $p->fresh()->title);
    }

    public function test_the_student_still_creates_and_edits_his_own_but_cannot_set_the_state(): void
    {
        $area = $this->area();
        $student = $this->user('ESTUDIANTE');
        $other = $this->user('ESTUDIANTE');
        $program = $this->program();
        Sanctum::actingAs($student);

        $created = $this->postJson('/api/proposals', [
            'user_id' => $other->id, 'status' => 'VIABLE', 'program_id' => $program->id, 'areas' => [$area->id],
            'title' => 'Mi idea', 'description' => 'Descripción de mi idea con más de veinte caracteres.',
        ])->assertStatus(201);

        $id = $created->json('proposal.id');
        $this->assertSame($student->id, (int) Proposal::find($id)->user_id, 'user_id del cliente ignorado.');
        $this->assertSame('RECIBIDA', Proposal::find($id)->status, 'status del cliente ignorado.');

        $this->putJson("/api/proposals/{$id}", [
            'program_id' => $program->id, 'areas' => [$area->id], 'title' => 'Mi idea editada',
            'description' => 'Descripción editada con más de veinte caracteres.', 'status' => 'VIABLE',
        ])->assertOk();
        $this->assertSame('Mi idea editada', Proposal::find($id)->title);
        $this->assertSame('RECIBIDA', Proposal::find($id)->status, 'No cambia el estado desde la edición.');
    }

    public function test_the_student_cannot_edit_an_evaluated_or_a_foreign_proposal(): void
    {
        $area = $this->area();
        $student = $this->user('ESTUDIANTE');
        $evaluated = $this->proposal([$area], ['status' => 'VIABLE'], $student);
        $foreign = $this->proposal([$area]);
        Sanctum::actingAs($student);
        $payload = ['program_id' => $evaluated->program_id, 'areas' => [$area->id], 'title' => 'X',
                    'description' => 'Descripción con más de veinte caracteres.'];

        $this->putJson("/api/proposals/{$evaluated->id}", $payload)->assertStatus(403);
        $this->putJson("/api/proposals/{$foreign->id}", $payload)->assertStatus(403);
    }
}
