<?php

namespace Tests\Feature;

use App\Models\Faculty;
use App\Models\Program;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Estados de propuesta con el vocabulario real de la spec (migración 2026_10_04_000001). */
class ProposalStatusVocabularyTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_04_000001_proposals_status_vocabulary.php';

    private function proposal(string $status): Proposal
    {
        $f = Faculty::firstOrCreate(['name' => 'F'], ['status' => 'ACTIVO']);
        $p = Program::firstOrCreate(['name' => 'P'], ['faculty_id' => $f->id, 'status' => 'ACTIVO']);

        return Proposal::create([
            'user_id' => User::factory()->create(['role' => 'ESTUDIANTE'])->id, 'program_id' => $p->id,
            'title' => 'Idea ' . uniqid(), 'description' => 'Descripción con suficiente longitud para la prueba.',
            'phone' => '3001234567', 'status' => $status,
        ]);
    }

    /* ── Los valores internos YA son los de la spec ── */

    public function test_a_new_proposal_is_stored_as_recibida_and_labelled_with_the_same_word(): void
    {
        $p = $this->proposal('RECIBIDA');

        $this->assertDatabaseHas('proposals', ['id' => $p->id, 'status' => 'RECIBIDA']);
        $this->assertSame('Recibida', $p->status_label);
        $this->assertSame(['Viable', 'Archivada'], [$this->proposal('VIABLE')->status_label, $this->proposal('ARCHIVADA')->status_label]);
    }

    public function test_the_old_internal_names_are_no_longer_valid_in_the_database(): void
    {
        $this->expectException(\Illuminate\Database\QueryException::class);
        $this->proposal('PENDIENTE');
    }

    public function test_normalize_status_accepts_both_vocabularies(): void
    {
        foreach (['PENDIENTE' => 'RECIBIDA', 'recibida' => 'RECIBIDA', 'APROBADA' => 'VIABLE', 'VIABLE' => 'VIABLE', 'RECHAZADA' => 'ARCHIVADA', ' archivada ' => 'ARCHIVADA'] as $in => $out) {
            $this->assertSame($out, Proposal::normalizeStatus($in), $in);
        }
        $this->assertNull(Proposal::normalizeStatus('XYZ'));
        $this->assertNull(Proposal::normalizeStatus(null));
    }

    /* ── Compatibilidad: clientes con la caché antigua siguen entendiéndose con la API ── */

    public function test_the_api_still_accepts_the_old_names_as_input(): void
    {
        $admin = User::factory()->create(['role' => 'ADMINISTRATIVO', 'status' => 'ACTIVO']);
        $a = $this->proposal('RECIBIDA'); $b = $this->proposal('RECIBIDA'); $c = $this->proposal('VIABLE');
        Sanctum::actingAs($admin);

        $this->putJson("/api/proposals/{$a->id}/update-status", ['status' => 'APROBADA'])->assertOk();
        $this->putJson("/api/proposals/{$b->id}/update-status", ['status' => 'RECHAZADA', 'review_note' => 'No encaja con las líneas.'])->assertOk();

        $this->assertSame(['VIABLE', 'ARCHIVADA'], [$a->fresh()->status, $b->fresh()->status]);
        $this->assertSame(2, count($this->getJson('/api/proposals?status=APROBADA')->assertOk()->json('proposals')), 'el filtro acepta el nombre antiguo');
        $this->assertSame(1, count($this->getJson('/api/proposals?status=ARCHIVADA')->assertOk()->json('proposals')));
        $this->assertSame($c->status, 'VIABLE');
    }

    public function test_the_api_returns_the_new_vocabulary(): void
    {
        $this->proposal('RECIBIDA');
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMINISTRATIVO', 'status' => 'ACTIVO']));

        $row = $this->getJson('/api/proposals')->assertOk()->json('proposals.0');

        $this->assertSame(['RECIBIDA', 'Recibida'], [$row['status'], $row['status_label']]);
    }

    /* ── La migración conserva los datos en los dos sentidos ── */

    public function test_the_migration_converts_existing_rows_and_can_be_rolled_back(): void
    {
        // Se vuelve al esquema anterior y se siembran filas con los nombres antiguos.
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION, '--force' => true])->assertExitCode(0);
        $f = Faculty::create(['name' => 'FM', 'status' => 'ACTIVO']);
        $prog = Program::create(['name' => 'PM', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
        foreach (['PENDIENTE', 'APROBADA', 'RECHAZADA'] as $i => $old) {
            DB::table('proposals')->insert([
                'user_id' => User::factory()->create()->id, 'program_id' => $prog->id, 'title' => "t$i", 'description' => 'd', 'status' => $old,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // Adelante: se convierten y se conserva cada propuesta.
        $this->artisan('migrate', ['--path' => self::MIGRATION, '--force' => true])->assertExitCode(0);
        $this->assertSame(['RECIBIDA', 'VIABLE', 'ARCHIVADA'], DB::table('proposals')->orderBy('id')->pluck('status')->all());
        $this->assertSame(3, DB::table('proposals')->count());

        // Atrás: vuelven los nombres antiguos y se vuelve a avanzar sin pérdidas.
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION, '--force' => true])->assertExitCode(0);
        $this->assertSame(['PENDIENTE', 'APROBADA', 'RECHAZADA'], DB::table('proposals')->orderBy('id')->pluck('status')->all());
        $this->artisan('migrate', ['--path' => self::MIGRATION, '--force' => true])->assertExitCode(0);
        $this->assertSame(['RECIBIDA', 'VIABLE', 'ARCHIVADA'], DB::table('proposals')->orderBy('id')->pluck('status')->all());
    }
}
