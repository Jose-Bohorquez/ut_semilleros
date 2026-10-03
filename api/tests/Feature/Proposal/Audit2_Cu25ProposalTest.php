<?php

namespace Tests\Feature\Proposal;

use Tests\TestCase;
use App\Models\User;
use App\Models\Proposal;
use App\Models\Area;
use App\Models\Faculty;
use App\Models\Program;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Auditoría 2 — CU25 Registrar propuesta: regresiones y cobertura faltante
 * (CU25-H1 áreas duplicadas/transacción, cifrado del teléfono, límites de la
 * descripción, RN15 ventana móvil, /proposals/my, reviewed_by).
 */
class Audit2_Cu25ProposalTest extends TestCase
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

    private function payload(User $student, array $overrides = []): array
    {
        return array_merge([
            'user_id'     => $student->id,
            'program_id'  => $this->programActivo()->id,
            'areas'       => [$this->area()->id],
            'title'       => 'Investigación sobre IA',
            'description' => 'Propuesta para aplicar IA en la educación superior de la región.',
            'phone'       => '3001234567',
            'status'      => 'PENDIENTE',
        ], $overrides);
    }

    private function student(): User
    {
        return User::factory()->create(['role' => 'ESTUDIANTE']);
    }

    /* ── CU25-H1 ── */

    public function test_duplicate_areas_are_rejected_and_no_proposal_is_created(): void
    {
        $student = $this->student();
        Sanctum::actingAs($student);
        $area = $this->area();

        $this->postJson('/api/proposals', $this->payload($student, ['areas' => [$area->id, $area->id]]))
            ->assertStatus(422)->assertJsonValidationErrors(['areas.0', 'areas.1']);

        $this->assertSame(0, Proposal::count());
        $this->assertSame(0, DB::table('proposal_area')->count());
    }

    public function test_duplicate_areas_are_rejected_on_update(): void
    {
        $student = $this->student();
        Sanctum::actingAs($student);   // CU27: el personal ya no edita propuestas; las edita su dueño
        $area = $this->area();
        $program = $this->programActivo();
        $proposal = Proposal::create([
            'user_id' => $student->id, 'program_id' => $program->id, 'title' => 'T',
            'description' => 'Descripción original con longitud suficiente.', 'status' => 'PENDIENTE',
        ]);
        $proposal->areas()->attach($area->id);

        $this->putJson("/api/proposals/{$proposal->id}", $this->payload($student, [
            'program_id' => $program->id, 'areas' => [$area->id, $area->id],
        ]))->assertStatus(422);

        $this->assertSame([$area->id], $proposal->areas()->pluck('areas.id')->all());
    }

    public function test_failure_attaching_areas_leaves_no_orphan_proposal(): void
    {
        $student = $this->student();
        Sanctum::actingAs($student);
        $payload = $this->payload($student);

        /* Simula un fallo del attach: sin la tabla pivote el INSERT revienta. */
        Schema::drop('proposal_area');

        $this->postJson('/api/proposals', $payload)->assertStatus(500);

        $this->assertSame(0, Proposal::count(), 'La propuesta debe revertirse junto con el fallo del attach.');
    }

    /* ── Teléfono cifrado (RNF03) ── */

    public function test_phone_is_encrypted_at_rest_and_decrypted_in_response(): void
    {
        $student = $this->student();
        Sanctum::actingAs($student);

        $res = $this->postJson('/api/proposals', $this->payload($student, ['phone' => '3001234567']))
            ->assertStatus(201);

        $this->assertSame('3001234567', $res->json('proposal.phone'));
        $raw = DB::table('proposals')->value('phone');
        $this->assertNotNull($raw);
        $this->assertNotSame('3001234567', $raw);
        $this->assertStringNotContainsString('3001234567', $raw);
    }

    /* ── Descripción: límites exactos ── */

    public function test_description_boundaries(): void
    {
        $student = $this->student();
        Sanctum::actingAs($student);

        $this->postJson('/api/proposals', $this->payload($student, ['description' => str_repeat('a', 19)]))
            ->assertStatus(422)->assertJsonValidationErrors(['description']);
        $this->postJson('/api/proposals', $this->payload($student, ['description' => str_repeat('a', 20)]))
            ->assertStatus(201);
        $this->postJson('/api/proposals', $this->payload($student, ['description' => str_repeat('a', 2000)]))
            ->assertStatus(201);
        $this->postJson('/api/proposals', $this->payload($student, ['description' => str_repeat('a', 2001)]))
            ->assertStatus(422)->assertJsonValidationErrors(['description']);
    }

    /* ── RN15: ventana móvil de 24 h por usuario ── */

    public function test_rate_limit_is_per_user(): void
    {
        $a = $this->student();
        $b = $this->student();

        Sanctum::actingAs($a);
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/proposals', $this->payload($a))->assertStatus(201);
        }
        $this->postJson('/api/proposals', $this->payload($a))->assertStatus(429);

        Sanctum::actingAs($b);
        $this->postJson('/api/proposals', $this->payload($b))->assertStatus(201);
    }

    public function test_proposals_older_than_24h_do_not_count(): void
    {
        $student = $this->student();
        Sanctum::actingAs($student);

        Carbon::setTestNow(now()->subHours(25));
        for ($i = 0; $i < 5; $i++) {
            $this->postJson('/api/proposals', $this->payload($student))->assertStatus(201);
        }
        Carbon::setTestNow();

        $this->postJson('/api/proposals', $this->payload($student))->assertStatus(201);
    }

    /* ── GET /proposals/my ── */

    public function test_my_proposals_returns_only_own_with_areas_and_program(): void
    {
        $mine  = $this->student();
        $other = $this->student();

        Sanctum::actingAs($other);
        $this->postJson('/api/proposals', $this->payload($other, ['title' => 'Ajena']))->assertStatus(201);
        Sanctum::actingAs($mine);
        $this->postJson('/api/proposals', $this->payload($mine, ['title' => 'Mía']))->assertStatus(201);

        $res = $this->getJson('/api/proposals/my')->assertStatus(200);
        $res->assertJsonCount(1, 'proposals');
        $this->assertSame('Mía', $res->json('proposals.0.title'));
        $this->assertCount(1, $res->json('proposals.0.areas'));
        $this->assertSame('Programa Test', $res->json('proposals.0.program.name'));
        // CU26 ya no expone user_id (dato interno): la propiedad se comprueba contra la BD.
        $this->assertSame($mine->id, (int) Proposal::findOrFail($res->json('proposals.0.id'))->user_id);
    }

    /* ── updateStatus: reviewed_by / reviewed_at ── */

    public function test_update_status_records_reviewer(): void
    {
        $evaluator = User::factory()->create(['role' => 'ADMINISTRATIVO']);   // CU27: solo el Administrativo evalúa
        $student = $this->student();
        $proposal = Proposal::create([
            'user_id' => $student->id, 'program_id' => $this->programActivo()->id, 'title' => 'T',
            'description' => 'Descripción original con longitud suficiente.', 'status' => 'PENDIENTE',
        ]);

        Sanctum::actingAs($evaluator);
        $this->putJson("/api/proposals/{$proposal->id}/update-status", ['status' => 'APROBADA'])
            ->assertStatus(200);

        $fresh = DB::table('proposals')->where('id', $proposal->id)->first();
        $this->assertSame('APROBADA', $fresh->status);
        $this->assertSame($evaluator->id, (int) $fresh->reviewed_by);
        $this->assertNotNull($fresh->reviewed_at);
    }
}
