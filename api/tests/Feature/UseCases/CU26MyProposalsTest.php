<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proposal;
use App\Models\Area;
use App\Models\Faculty;
use App\Models\Program;
use Laravel\Sanctum\Sanctum;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU26 — Consultar mis propuestas (RF11, RN13; RNF02, RNF03, RNF12).
 * Cada test cita el paso / alterno / regla de la especificación que demuestra.
 */
class CU26MyProposalsTest extends TestCase
{
    use RefreshDatabase;

    private function student(): User
    {
        return User::factory()->create(['role' => 'ESTUDIANTE']);
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

    private function proposal(User $owner, array $attrs = []): Proposal
    {
        $p = Proposal::create(array_merge([
            'user_id'     => $owner->id,
            'program_id'  => $this->program()->id,
            'title'       => 'Idea ' . uniqid(),
            'description' => 'Descripción completa de la propuesta para la consulta de CU26.',
            'phone'       => '3001234567',
            'status'      => 'PENDIENTE',
        ], $attrs));
        $p->areas()->attach($this->area()->id);
        return $p;
    }

    /* ── Acceso y RN13 ── */

    public function test_unauthenticated_gets_401(): void
    {
        $this->getJson('/api/proposals/my')->assertStatus(401);
    }

    public function test_non_student_roles_are_denied(): void
    {
        foreach (['ADMIN_SISTEMA', 'ADMINISTRATIVO', 'LIDER_SEMILLERO'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->getJson('/api/proposals/my')->assertStatus(403);
        }
    }

    /** Paso 2 / RN13 / criterio de aceptación de RF11: «El estudiante solo ve sus propuestas». */
    public function test_student_sees_only_own_proposals(): void
    {
        $a = $this->student();
        $b = $this->student();
        $mine1 = $this->proposal($a);
        $mine2 = $this->proposal($a);
        $theirs = $this->proposal($b);

        Sanctum::actingAs($a);
        $ids = collect($this->getJson('/api/proposals/my')->assertOk()->json('proposals'))->pluck('id')->all();

        $this->assertEqualsCanonicalizing([$mine1->id, $mine2->id], $ids);
        $this->assertNotContains($theirs->id, $ids);
    }

    public function test_a_query_parameter_cannot_widen_the_scope(): void
    {
        $a = $this->student();
        $b = $this->student();
        $this->proposal($a);
        $theirs = $this->proposal($b);

        Sanctum::actingAs($a);
        $ids = collect($this->getJson("/api/proposals/my?user_id={$b->id}")->assertOk()->json('proposals'))->pluck('id')->all();
        $this->assertNotContains($theirs->id, $ids);
    }

    /* ── Paso 3: fecha, áreas, programa, estado y observación ── */

    public function test_each_item_has_date_areas_program_state_and_review_note(): void
    {
        $s = $this->student();
        $this->proposal($s);

        Sanctum::actingAs($s);
        $this->getJson('/api/proposals/my')->assertOk()->assertJsonStructure([
            'proposals' => [[
                'id', 'created_at', 'title', 'description',
                'program' => ['id', 'name'], 'areas' => [['id', 'name']],
                'status', 'status_label', 'review_note', 'reviewed_at',
            ]],
        ]);
    }

    /** Paso 3: «estado (Recibida, Viable, Archivada)». */
    public function test_state_labels_follow_the_specification_vocabulary(): void
    {
        $s = $this->student();
        $recibida  = $this->proposal($s, ['status' => 'PENDIENTE']);
        $viable    = $this->proposal($s, ['status' => 'APROBADA']);
        $archivada = $this->proposal($s, ['status' => 'RECHAZADA']);

        Sanctum::actingAs($s);
        $byId = collect($this->getJson('/api/proposals/my')->assertOk()->json('proposals'))->keyBy('id');

        $this->assertSame('Recibida',  $byId[$recibida->id]['status_label']);
        $this->assertSame('Viable',    $byId[$viable->id]['status_label']);
        $this->assertSame('Archivada', $byId[$archivada->id]['status_label']);
    }

    public function test_review_note_is_null_until_evaluated_and_visible_afterwards(): void
    {
        $s = $this->student();
        $p = $this->proposal($s);

        Sanctum::actingAs($s);
        $this->assertNull($this->getJson('/api/proposals/my')->json('proposals.0.review_note'));

        // El evaluador (Administrativo, CU27) responde: CU27 paso 7 «el estudiante ve el nuevo estado en CU26».
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));
        $this->putJson("/api/proposals/{$p->id}/update-status", [
            'status' => 'APROBADA', 'review_note' => 'Es viable, agenda una reunión con el líder.',
        ])->assertOk();

        Sanctum::actingAs($s);
        $item = $this->getJson('/api/proposals/my')->json('proposals.0');
        $this->assertSame('Viable', $item['status_label']);
        $this->assertSame('Es viable, agenda una reunión con el líder.', $item['review_note']);
        $this->assertNotNull($item['reviewed_at']);
    }

    public function test_review_note_length_is_validated_and_optional(): void
    {
        $s = $this->student();
        $p = $this->proposal($s);
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO']));

        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'APROBADA', 'review_note' => 'no'])
            ->assertStatus(422)->assertJsonValidationErrors(['review_note']);
        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'APROBADA'])->assertOk();
    }

    /** Paso 5: el detalle trae la descripción COMPLETA (sin recortar). */
    public function test_full_description_is_returned_for_the_detail(): void
    {
        $s = $this->student();
        $long = str_repeat('Descripción larga. ', 100); // ~1900 caracteres
        $this->proposal($s, ['description' => $long]);

        Sanctum::actingAs($s);
        $this->assertSame($long, $this->getJson('/api/proposals/my')->json('proposals.0.description'));
    }

    public function test_orders_newest_first(): void
    {
        $s = $this->student();
        $old = $this->proposal($s);
        $old->forceFill(['created_at' => now()->subDays(3)])->save();
        $new = $this->proposal($s);

        Sanctum::actingAs($s);
        $ids = collect($this->getJson('/api/proposals/my')->json('proposals'))->pluck('id')->all();
        $this->assertSame([$new->id, $old->id], $ids);
    }

    /* ── A1: sin propuestas ── */

    public function test_empty_list_returns_an_empty_array(): void
    {
        Sanctum::actingAs($this->student());
        $this->getJson('/api/proposals/my')->assertOk()->assertExactJson(['proposals' => []]);
    }

    /* ── RNF03 / RNF12: datos personales ── */

    public function test_response_never_exposes_owner_id_or_reviewer_identity(): void
    {
        $s = $this->student();
        $reviewer = User::factory()->create(['role' => 'ADMINISTRATIVO']);
        $p = $this->proposal($s);
        $p->forceFill(['reviewed_by' => $reviewer->id, 'reviewed_at' => now()])->save();

        Sanctum::actingAs($s);
        $item = $this->getJson('/api/proposals/my')->assertOk()->json('proposals.0');

        foreach (['user_id', 'user', 'reviewed_by'] as $forbidden) {
            $this->assertArrayNotHasKey($forbidden, $item);
        }
        $this->assertStringNotContainsString($reviewer->email, json_encode($item));
    }

    public function test_phone_is_stored_encrypted_and_returned_only_to_its_owner(): void
    {
        $s = $this->student();
        $p = $this->proposal($s, ['phone' => '3105557788']);

        $this->assertNotSame('3105557788', DB::table('proposals')->where('id', $p->id)->value('phone'));

        Sanctum::actingAs($s);
        $this->assertSame('3105557788', $this->getJson('/api/proposals/my')->json('proposals.0.phone'));
    }

    /* ── Compatibilidad: el resto de la API conserva el estado interno ── */

    public function test_internal_status_is_kept_for_existing_consumers(): void
    {
        $s = $this->student();
        $this->proposal($s, ['status' => 'PENDIENTE']);

        Sanctum::actingAs($s);
        $this->assertSame('PENDIENTE', $this->getJson('/api/proposals/my')->json('proposals.0.status'));
    }
}
