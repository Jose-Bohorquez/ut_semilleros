<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Cat;
use App\Models\Coordinator;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Catálogo del IDEAD y carga masiva de semilleros desde un JSON (comandos catalog:idead y seedbeds:import). */
class IdeadImportTest extends TestCase
{
    use RefreshDatabase;

    private function file(array $rows): string
    {
        $path = sys_get_temp_dir() . '/idead_' . uniqid() . '.json';
        file_put_contents($path, json_encode($rows, JSON_UNESCAPED_UNICODE));

        return $path;
    }

    private function row(string $code, array $over = []): array
    {
        return array_merge([
            'code' => $code, 'title' => "Semillero {$code}", 'objective' => 'Un objetivo general con suficiente longitud para el registro.',
            'coordinator' => 'Ana Pérez', 'email' => "ana.{$code}@ut.edu.co", 'phone' => '3001234567', 'areas' => ['Humanidades'],
        ], $over);
    }

    private function areas(): void
    {
        foreach (['Humanidades', 'Ciencias Naturales'] as $i => $n) {
            Area::create(['name' => $n, 'code' => "A{$i}", 'status' => 'ACTIVO']);
        }
    }

    /* ── Catálogo ── */

    public function test_the_catalog_creates_the_faculty_12_programs_and_21_cats(): void
    {
        $this->artisan('catalog:idead')->assertExitCode(0);

        $f = Faculty::where('code', 'IDEAD')->firstOrFail();
        $this->assertSame(12, $f->programs()->count());
        $this->assertSame('PREGRADO', $f->programs()->first()->type);
        $this->assertSame(21, Cat::count());
        $this->assertDatabaseHas('programs', ['code' => '0854', 'name' => 'Ingeniería de Sistemas']);
        $this->assertDatabaseHas('cats', ['code' => 'BOG-KENNEDY', 'name' => 'CAT Bogotá Kennedy']);
    }

    public function test_the_catalog_is_idempotent_and_never_duplicates(): void
    {
        $this->artisan('catalog:idead')->assertExitCode(0);
        $this->artisan('catalog:idead')->expectsOutputToContain('Creados: 0 facultad, 0 programas, 0 CAT')->assertExitCode(0);

        $this->assertSame([1, 12, 21], [Faculty::count(), Program::count(), Cat::count()]);
    }

    public function test_the_catalog_reuses_an_existing_distance_faculty(): void
    {
        $existing = Faculty::create(['code' => 'FED', 'name' => 'Facultad de Educación a Distancia', 'status' => 'ACTIVO']);

        $this->artisan('catalog:idead')->assertExitCode(0);

        $this->assertSame(1, Faculty::count());
        $this->assertSame(12, $existing->programs()->count());
    }

    public function test_the_catalog_dry_run_writes_nothing(): void
    {
        $this->artisan('catalog:idead --dry-run')->expectsOutputToContain('[simulación]')->assertExitCode(0);

        $this->assertSame([0, 0, 0], [Faculty::count(), Program::count(), Cat::count()]);
    }

    public function test_the_presencial_catalog_creates_27_programs_in_9_faculties_idempotently(): void
    {
        $this->artisan('catalog:presencial')->assertExitCode(0);
        $this->artisan('catalog:presencial')->expectsOutputToContain('Creados: 0 facultades, 0 programas')->assertExitCode(0);

        $this->assertSame(9, Faculty::count());
        $this->assertSame(29, Program::count());
        $this->assertSame('Facultad de Ciencias de la Salud', Program::where('code', '1002')->first()->faculty->name);
        $this->assertSame(0, Program::whereNull('faculty_id')->count());
    }

    public function test_presencial_and_distance_catalogs_coexist(): void
    {
        $this->artisan('catalog:idead'); $this->artisan('catalog:presencial');

        $this->assertSame(41, Program::count());
        $this->assertSame(10, Faculty::count());
    }

    /* ── Importación de semilleros ── */

    public function test_import_requires_the_catalog_first(): void
    {
        $this->artisan('seedbeds:import', ['file' => $this->file([$this->row('X1')])])->assertExitCode(1);
    }

    public function test_it_imports_seedbeds_with_coordinator_programs_and_areas_as_inactive_drafts(): void
    {
        $this->artisan('catalog:idead'); $this->areas();

        $this->artisan('seedbeds:import', ['file' => $this->file([$this->row('IDEAD-001'), $this->row('IDEAD-002', ['areas' => ['Humanidades', 'Ciencias Naturales']])])])
            ->assertExitCode(0);

        $s = Seedbed::where('code', 'IDEAD-002')->firstOrFail();
        $this->assertSame('INACTIVO', $s->status, 'por defecto quedan en borrador, sin publicar');
        $this->assertSame(12, $s->programs()->count());
        $this->assertSame(2, $s->areas()->count());
        $this->assertSame('ana.IDEAD-002@ut.edu.co', Coordinator::find($s->coordinator_id)->email === 'ana.idead-002@ut.edu.co' ? 'ana.IDEAD-002@ut.edu.co' : 'otro');
        $this->assertStringContainsString('Pendiente de acta', $s->authorization_reference);
        $this->assertSame(2, Coordinator::count());
    }

    public function test_publish_activates_only_the_complete_ones_and_missing_objectives_get_a_placeholder(): void
    {
        $this->artisan('catalog:idead'); $this->areas();

        $this->artisan('seedbeds:import', ['file' => $this->file([$this->row('C1'), $this->row('C2', ['objective' => null])]), '--publish' => true])
            ->expectsOutputToContain('sin objetivo (borrador): 1')->assertExitCode(0);

        $this->assertSame('ACTIVO', Seedbed::where('code', 'C1')->value('status'));
        $c2 = Seedbed::where('code', 'C2')->firstOrFail();
        $this->assertSame('INACTIVO', $c2->status);
        $this->assertStringContainsString('por definir', $c2->objetivo_general);
    }

    public function test_it_is_idempotent_and_only_fills_the_gaps_of_an_existing_seedbed(): void
    {
        $this->artisan('catalog:idead'); $this->areas();
        $mine = Seedbed::create(['code' => '220424', 'name' => 'Nombre que ya existía', 'status' => 'ACTIVO', 'authorization_reference' => '123']);
        $file = $this->file([$this->row('220424', ['title' => 'Otro nombre']), $this->row('N1')]);

        $this->artisan('seedbeds:import', ['file' => $file])->assertExitCode(0);
        $this->artisan('seedbeds:import', ['file' => $file])->expectsOutputToContain('Semilleros nuevos: 0')->assertExitCode(0);

        $fresh = $mine->fresh();
        $this->assertSame('Nombre que ya existía', $fresh->name, 'el nombre no se sobrescribe');
        $this->assertSame('123', $fresh->authorization_reference);
        $this->assertNotNull($fresh->coordinator_id, 'se le completa el coordinador que faltaba');
        $this->assertStringContainsString('suficiente longitud', $fresh->objetivo_general);
        $this->assertSame(1, $fresh->areas()->count());
        $this->assertSame(2, Seedbed::count());
        $this->assertSame(2, Coordinator::count());
    }

    public function test_a_coordinator_with_the_same_email_is_reused(): void
    {
        $this->artisan('catalog:idead'); $this->areas();
        $same = ['email' => 'misma@ut.edu.co'];

        $this->artisan('seedbeds:import', ['file' => $this->file([$this->row('S1', $same), $this->row('S2', $same)])])->assertExitCode(0);

        $this->assertSame(1, Coordinator::count());
        $this->assertSame(Seedbed::where('code', 'S1')->value('coordinator_id'), Seedbed::where('code', 'S2')->value('coordinator_id'));
    }

    public function test_dry_run_writes_nothing_and_rows_without_a_valid_email_are_skipped(): void
    {
        $this->artisan('catalog:idead'); $this->areas();

        $this->artisan('seedbeds:import', ['file' => $this->file([$this->row('D1'), $this->row('D2', ['email' => 'no-es-correo'])]), '--dry-run' => true])
            ->expectsOutputToContain('[simulación]')->assertExitCode(0);
        $this->assertSame([0, 0], [Seedbed::count(), Coordinator::count()]);

        $this->artisan('seedbeds:import', ['file' => $this->file([$this->row('D2', ['email' => 'no-es-correo'])])])->assertExitCode(0);
        $this->assertSame(0, Seedbed::count());
    }

    public function test_an_unreadable_file_fails_cleanly(): void
    {
        $this->artisan('catalog:idead');
        $this->artisan('seedbeds:import', ['file' => '/no/existe.json'])->assertExitCode(1);
        $bad = sys_get_temp_dir() . '/bad_' . uniqid() . '.json'; file_put_contents($bad, '{no es json');
        $this->artisan('seedbeds:import', ['file' => $bad])->assertExitCode(1);
    }
}
