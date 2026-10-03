<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Http\Controllers\Api\ReportController;
use App\Models\Area;
use App\Models\Cat;
use App\Models\Faculty;
use App\Models\MembershipRequest;
use App\Models\Program;
use App\Models\Proposal;
use App\Models\Seedbed;
use App\Models\SeedbedMember;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU28 — Consultar reportes y estadísticas (RF15, RN06, RNF07). Cada test cita el paso, alterno,
 * excepción o criterio de aceptación de la especificación que demuestra.
 */
class CU28ReportsTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'ACTIVO']);
    }

    private function faculty(string $name): Faculty
    {
        return Faculty::create(['name' => $name, 'status' => 'ACTIVO']);
    }

    private function program(Faculty $f, string $name): Program
    {
        return Program::create(['name' => $name, 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
    }

    private function cat(string $name): Cat
    {
        return Cat::create(['name' => $name, 'code' => 'C-' . uniqid(), 'phone1' => '3001112233', 'status' => 'ACTIVO']);
    }

    private function seedbed(string $name, array $programs, ?Cat $cat = null, string $status = 'ACTIVO', ?User $leader = null, array $areas = []): Seedbed
    {
        $s = Seedbed::create(['name' => $name, 'status' => $status, 'cat_id' => $cat?->id]);
        $s->programs()->attach(array_map(fn ($p) => $p->id, $programs));
        if ($areas) {
            $s->areas()->attach(array_map(fn ($a) => $a->id, $areas));
        }
        if ($leader) {
            $s->users()->attach($leader->id, ['role' => 'LIDER']);
        }
        return $s;
    }

    private function request(Seedbed $s, string $status, ?string $createdAt = null): MembershipRequest
    {
        $r = MembershipRequest::create([
            'user_id' => $this->user('ESTUDIANTE')->id, 'seedbed_id' => $s->id, 'status' => $status,
            'phone' => '3001234567', 'message' => 'Solicitud de prueba para los reportes.',
        ]);
        if ($createdAt) {
            $r->forceFill(['created_at' => $createdAt])->save();
        }
        return $r;
    }

    private function member(Seedbed $s, Program $p, string $level, string $status = 'ACTIVO', ?string $createdAt = null): SeedbedMember
    {
        $m = SeedbedMember::create([
            'seedbed_id' => $s->id, 'name' => 'Integrante ' . uniqid(), 'student_code' => 'C' . uniqid(),
            'program_id' => $p->id, 'level' => $level, 'email' => uniqid() . '@ut.edu.co', 'status' => $status,
        ]);
        if ($createdAt) {
            $m->forceFill(['created_at' => $createdAt])->save();
        }
        return $m;
    }

    private function area(string $name): Area
    {
        return Area::create(['name' => $name, 'code' => 'A-' . uniqid(), 'status' => 'ACTIVO']);
    }

    private function proposal(array $areas, ?string $createdAt = null): Proposal
    {
        $p = Proposal::create([
            'user_id' => $this->user('ESTUDIANTE')->id, 'program_id' => $this->program($this->faculty('F' . uniqid()), 'P' . uniqid())->id,
            'title' => 'Idea', 'description' => 'Descripción de prueba con más de veinte caracteres.', 'status' => 'PENDIENTE',
        ]);
        $p->areas()->attach(array_map(fn ($a) => $a->id, $areas));
        if ($createdAt) {
            $p->forceFill(['created_at' => $createdAt])->save();
        }
        return $p;
    }

    private function report(string $key, string $query = '')
    {
        return $this->getJson('/api/reports' . ($query ? "?$query" : ''))->assertOk()->json("reports.$key");
    }

    /* ── Acceso ── */

    public function test_unauthenticated_gets_401(): void
    {
        foreach (['', '/options', '/export?report=top_areas'] as $suffix) {
            $this->getJson("/api/reports$suffix")->assertStatus(401);
        }
    }

    public function test_students_get_403_on_every_endpoint(): void
    {
        Sanctum::actingAs($this->user('ESTUDIANTE'));
        foreach (['', '/options', '/export?report=top_areas'] as $suffix) {
            $this->getJson("/api/reports$suffix")->assertStatus(403);
        }
    }

    public function test_the_three_staff_roles_can_open_the_reports(): void
    {
        foreach (['ADMIN_SISTEMA', 'ADMINISTRATIVO', 'LIDER_SEMILLERO'] as $role) {
            Sanctum::actingAs($this->user($role));
            $this->getJson('/api/reports')->assertOk()->assertJsonStructure([
                'reports' => ['seedbeds_by_faculty', 'requests_by_seedbed', 'top_areas', 'members_by_program_level'], 'message',
            ]);
        }
    }

    /* ── Paso 2: los cuatro indicadores, y RF15: «los totales coinciden con los conteos directos» ── */

    public function test_seedbeds_by_faculty_counts_only_active_seedbeds_and_matches_a_direct_count(): void
    {
        $f1 = $this->faculty('Ingeniería'); $f2 = $this->faculty('Educación');
        $p1 = $this->program($f1, 'Sistemas'); $p2 = $this->program($f2, 'Pedagogía'); $p3 = $this->program($f1, 'Civil');
        $this->seedbed('S1', [$p1, $p2]);      // dos facultades: cuenta en cada una
        $this->seedbed('S2', [$p3]);
        $this->seedbed('S3', [$p1], null, 'INACTIVO');   // inactivo: no cuenta
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $r = $this->report('seedbeds_by_faculty');

        $byName = collect($r['rows'])->pluck('total', 'faculty')->all();
        $this->assertSame(['Ingeniería' => 2, 'Educación' => 1], $byName);
        $this->assertSame('Ingeniería', $r['rows'][0]['faculty'], 'Ordenado de mayor a menor.');
        $this->assertSame(2, $r['total'], 'Total = semilleros activos distintos.');
        $this->assertSame(Seedbed::where('status', 'ACTIVO')->count(), $r['total']);
    }

    public function test_requests_by_seedbed_and_state_match_direct_counts(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $s1 = $this->seedbed('Semillero A', [$p]); $s2 = $this->seedbed('Semillero B', [$p]);
        foreach (['PENDIENTE', 'PENDIENTE', 'APROBADA'] as $st) { $this->request($s1, $st); }
        $this->request($s2, 'RECHAZADA');
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $r = $this->report('requests_by_seedbed');

        $rows = collect($r['rows'])->keyBy('seedbed');
        $this->assertSame([2, 1, 0, 3], [$rows['Semillero A']['pendientes'], $rows['Semillero A']['aprobadas'], $rows['Semillero A']['rechazadas'], $rows['Semillero A']['total']]);
        $this->assertSame([0, 0, 1, 1], [$rows['Semillero B']['pendientes'], $rows['Semillero B']['aprobadas'], $rows['Semillero B']['rechazadas'], $rows['Semillero B']['total']]);
        $this->assertSame(MembershipRequest::count(), $r['total']);
    }

    public function test_top_areas_returns_the_ten_areas_with_most_proposals_ordered_with_name_tiebreak(): void
    {
        $areas = [];
        foreach (range(1, 12) as $i) { $areas[$i] = $this->area(sprintf('Área %02d', $i)); }
        // El área i recibe (13 - i) propuestas, salvo las áreas 11 y 12 que quedan con 2 y 1.
        foreach ($areas as $i => $a) {
            foreach (range(1, 13 - $i) as $_) { $this->proposal([$a]); }
        }
        $this->proposal([$areas[2], $areas[3]]);   // una propuesta con dos áreas cuenta en cada una
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $r = $this->report('top_areas');

        $this->assertCount(10, $r['rows'], 'Solo las 10 áreas con más propuestas.');
        $names = array_column($r['rows'], 'area');
        $this->assertSame('Área 01', $names[0]);
        $this->assertNotContains('Área 11', $names);
        $this->assertNotContains('Área 12', $names);
        $this->assertSame(11 + 1, collect($r['rows'])->firstWhere('area', 'Área 02')['total'], '11 propias + 1 compartida.');
    }

    public function test_members_by_program_and_level_count_only_active_members(): void
    {
        $f = $this->faculty('F');
        $sis = $this->program($f, 'Sistemas'); $civ = $this->program($f, 'Civil');
        $s = $this->seedbed('S', [$sis, $civ]);
        $this->member($s, $sis, 'PR'); $this->member($s, $sis, 'PR'); $this->member($s, $sis, 'PG');
        $this->member($s, $civ, 'PR');
        $this->member($s, $civ, 'PR', 'INACTIVO');   // inactivo: no cuenta
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $r = $this->report('members_by_program_level');

        $rows = collect($r['rows'])->mapWithKeys(fn ($x) => ["{$x['program']}|{$x['level']}" => $x['total']])->all();
        $this->assertSame(['Civil|PR' => 1, 'Sistemas|PG' => 1, 'Sistemas|PR' => 2], $rows);
        $this->assertSame('Pregrado', collect($r['rows'])->firstWhere('level', 'PR')['level_label']);
        $this->assertSame(SeedbedMember::where('status', 'ACTIVO')->count(), $r['total']);
    }

    /* ── Pasos 3-4: filtros por CAT y por rango de fechas ── */

    public function test_the_cat_filter_applies_to_seedbeds_requests_and_members_but_not_to_proposals(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $c1 = $this->cat('CAT Uno'); $c2 = $this->cat('CAT Dos');
        $a = $this->seedbed('En CAT 1', [$p], $c1); $b = $this->seedbed('En CAT 2', [$p], $c2);
        $this->request($a, 'PENDIENTE'); $this->request($b, 'PENDIENTE'); $this->request($b, 'APROBADA');
        $this->member($a, $p, 'PR'); $this->member($b, $p, 'PG');
        $area = $this->area('Área'); $this->proposal([$area]); $this->proposal([$area]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $q = "cat_id={$c2->id}";
        $this->assertSame(1, $this->report('seedbeds_by_faculty', $q)['total']);
        $this->assertSame(['En CAT 2'], array_column($this->report('requests_by_seedbed', $q)['rows'], 'seedbed'));
        $this->assertSame(2, $this->report('requests_by_seedbed', $q)['total']);
        $this->assertSame(['PG'], array_column($this->report('members_by_program_level', $q)['rows'], 'level'));
        $this->assertSame(2, $this->report('top_areas', $q)['rows'][0]['total'], 'Las propuestas no tienen CAT: el filtro no las afecta.');
    }

    public function test_the_date_range_uses_whole_days_in_bogota_time(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $s = $this->seedbed('S', [$p]);
        $this->request($s, 'PENDIENTE', '2026-03-10 04:59:59');   // 09-mar 23:59:59 Bogotá: fuera
        $this->request($s, 'PENDIENTE', '2026-03-10 05:00:00');   // 10-mar 00:00:00: dentro
        $this->request($s, 'APROBADA',  '2026-03-11 04:59:59');   // 10-mar 23:59:59: dentro
        $this->request($s, 'RECHAZADA', '2026-03-11 05:00:00');   // 11-mar 00:00:00: fuera
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $r = $this->report('requests_by_seedbed', 'from=2026-03-10&to=2026-03-10');

        $this->assertSame(2, $r['total']);
        $this->assertSame([1, 1, 0], [$r['rows'][0]['pendientes'], $r['rows'][0]['aprobadas'], $r['rows'][0]['rechazadas']]);
    }

    public function test_the_date_range_filters_proposals_and_members_by_their_registration_date(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $s = $this->seedbed('S', [$p]);
        $area = $this->area('Área');
        $this->proposal([$area], '2026-01-15 10:00:00'); $this->proposal([$area], '2026-02-15 10:00:00');
        $this->member($s, $p, 'PR', 'ACTIVO', '2026-01-15 10:00:00'); $this->member($s, $p, 'PR', 'ACTIVO', '2026-02-15 10:00:00');
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->assertSame(1, $this->report('top_areas', 'from=2026-02-01&to=2026-02-28')['total']);
        $this->assertSame(1, $this->report('members_by_program_level', 'from=2026-02-01&to=2026-02-28')['total']);
        $this->assertSame(2, $this->report('members_by_program_level')['total'], 'Sin filtro cuenta todo.');
    }

    public function test_invalid_filters_are_rejected(): void
    {
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));
        $this->getJson('/api/reports?from=10/03/2026')->assertStatus(422)->assertJsonValidationErrors(['from']);
        $this->getJson('/api/reports?from=2026-03-10&to=2026-03-01')->assertStatus(422)->assertJsonValidationErrors(['to']);
        $this->getJson('/api/reports?cat_id=abc')->assertStatus(422)->assertJsonValidationErrors(['cat_id']);
    }

    /* ── A1: el Líder ve los indicadores solo de sus semilleros (RN06) ── */

    public function test_a1_the_leader_only_sees_his_own_seedbeds_in_every_indicator(): void
    {
        $f = $this->faculty('F'); $p = $this->program($f, 'P');
        $mine = $this->area('Mía'); $other = $this->area('Ajena');
        $leader = $this->user('LIDER_SEMILLERO');
        $s1 = $this->seedbed('Mi semillero', [$p], null, 'ACTIVO', $leader, [$mine]);
        $s2 = $this->seedbed('Semillero ajeno', [$p], null, 'ACTIVO', $this->user('LIDER_SEMILLERO'), [$other]);
        $this->request($s1, 'PENDIENTE'); $this->request($s2, 'PENDIENTE'); $this->request($s2, 'APROBADA');
        $this->member($s1, $p, 'PR'); $this->member($s2, $p, 'PG');
        $this->proposal([$mine]); $this->proposal([$other]); $this->proposal([$other]);
        Sanctum::actingAs($leader);

        $this->assertSame(1, $this->report('seedbeds_by_faculty')['total']);
        $this->assertSame(['Mi semillero'], array_column($this->report('requests_by_seedbed')['rows'], 'seedbed'));
        $this->assertSame(['PR'], array_column($this->report('members_by_program_level')['rows'], 'level'));
        $areas = $this->report('top_areas');
        $this->assertSame(['Mía'], array_column($areas['rows'], 'area'), 'Solo las áreas de sus semilleros.');
        $this->assertSame(1, $areas['total']);
    }

    public function test_a1_a_leader_without_seedbeds_sees_no_data(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $s = $this->seedbed('Ajeno', [$p], null, 'ACTIVO', $this->user('LIDER_SEMILLERO'));
        $this->request($s, 'PENDIENTE'); $this->member($s, $p, 'PR');
        Sanctum::actingAs($this->user('LIDER_SEMILLERO'));

        $res = $this->getJson('/api/reports')->assertOk();
        foreach ($res->json('reports') as $report) {
            $this->assertSame([], $report['rows']);
        }
        $this->assertSame('Sin datos para el periodo', $res->json('message'));
    }

    public function test_the_leader_only_gets_the_cats_of_his_seedbeds_as_filter_options(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $mine = $this->cat('CAT mío'); $other = $this->cat('CAT ajeno');
        $leader = $this->user('LIDER_SEMILLERO');
        $this->seedbed('Mío', [$p], $mine, 'ACTIVO', $leader);
        $this->seedbed('Ajeno', [$p], $other, 'ACTIVO', $this->user('LIDER_SEMILLERO'));

        Sanctum::actingAs($leader);
        $this->assertSame(['CAT mío'], array_column($this->getJson('/api/reports/options')->assertOk()->json('cats'), 'name'));

        Sanctum::actingAs($this->user('ADMINISTRATIVO'));
        $this->assertEqualsCanonicalizing(['CAT mío', 'CAT ajeno'], array_column($this->getJson('/api/reports/options')->json('cats'), 'name'));
    }

    /* ── E1: «Sin datos para el periodo» ── */

    public function test_e1_no_data_in_the_range_returns_the_exact_message(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $s = $this->seedbed('S', [$p]);
        $this->request($s, 'PENDIENTE', '2026-03-10 12:00:00');
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->assertSame('Sin datos para el periodo', $this->getJson('/api/reports?from=2030-01-01&to=2030-01-31')->assertOk()->json('message'));
        $this->assertNull($this->getJson('/api/reports')->assertOk()->json('message'), 'Con datos no hay mensaje.');
    }

    /* ── Pasos 5-6: exportar CSV del reporte seleccionado ── */

    public function test_export_returns_a_csv_with_header_rows_and_a_total_for_each_report(): void
    {
        $f = $this->faculty('Ingeniería'); $p = $this->program($f, 'Sistemas');
        $s = $this->seedbed('Semillero A', [$p]);
        $this->request($s, 'PENDIENTE'); $this->request($s, 'APROBADA');
        $this->member($s, $p, 'PR');
        $this->proposal([$this->area('Área uno')]);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $expect = [
            'seedbeds_by_faculty'      => ['Facultad,"Semilleros activos"', 'Ingeniería,1', '"Total (semilleros distintos)",1'],
            'requests_by_seedbed'      => ['Semillero,Pendientes,Aprobadas,Rechazadas,Total', '"Semillero A",1,1,0,2', 'Total,1,1,0,2'],
            'top_areas'                => ['Área,Propuestas', '"Área uno",1', 'Total,1'],
            'members_by_program_level' => ['Programa,Nivel,"Integrantes activos"', 'Sistemas,Pregrado,1', 'Total,,1'],
        ];

        foreach ($expect as $key => $lines) {
            $res = $this->get("/api/reports/export?report=$key")->assertOk();
            $csv = $res->streamedContent();
            $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'), $key);
            $this->assertStringContainsString("reporte_{$key}_", $res->headers->get('Content-Disposition'), $key);
            $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, "$key: BOM UTF-8");
            foreach ($lines as $line) {
                $this->assertStringContainsString($line, $csv, $key);
            }
        }
    }

    public function test_export_applies_the_same_filters_as_the_screen(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $c1 = $this->cat('Uno'); $c2 = $this->cat('Dos');
        $this->seedbed('En CAT 1', [$p], $c1); $this->seedbed('En CAT 2', [$p], $c2);
        $s1 = Seedbed::where('name', 'En CAT 1')->first(); $s2 = Seedbed::where('name', 'En CAT 2')->first();
        $this->request($s1, 'PENDIENTE'); $this->request($s2, 'PENDIENTE');
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $csv = $this->get("/api/reports/export?report=requests_by_seedbed&cat_id={$c2->id}")->streamedContent();

        $this->assertStringContainsString('"En CAT 2"', $csv);
        $this->assertStringNotContainsString('"En CAT 1"', $csv);
    }

    public function test_export_of_the_leader_only_contains_his_data(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $leader = $this->user('LIDER_SEMILLERO');
        $mine = $this->seedbed('Mío', [$p], null, 'ACTIVO', $leader);
        $other = $this->seedbed('Ajeno', [$p], null, 'ACTIVO', $this->user('LIDER_SEMILLERO'));
        $this->request($mine, 'PENDIENTE'); $this->request($other, 'PENDIENTE');
        Sanctum::actingAs($leader);

        $csv = $this->get('/api/reports/export?report=requests_by_seedbed')->streamedContent();

        $this->assertStringContainsString('Mío', $csv);
        $this->assertStringNotContainsString('Ajeno', $csv);
    }

    public function test_export_requires_a_known_report_and_validates_filters(): void
    {
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));
        $this->getJson('/api/reports/export')->assertStatus(422)->assertJsonValidationErrors(['report']);
        $this->getJson('/api/reports/export?report=inventado')->assertStatus(422)->assertJsonValidationErrors(['report']);
        $this->getJson('/api/reports/export?report=top_areas&from=2026-03-10&to=2026-03-01')->assertStatus(422);
    }

    public function test_export_neutralises_csv_formula_injection(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $s = $this->seedbed('=HYPERLINK("http://malo.example","clic")', [$p]);
        $this->request($s, 'PENDIENTE');
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $csv = $this->get('/api/reports/export?report=requests_by_seedbed')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertDoesNotMatchRegularExpression('/(^|\n)"?=HYPERLINK/', $csv);
    }

    /* ── E2: la consulta supera 10 s ── */

    public function test_e2_recognises_a_statement_timeout_from_mysql_and_mariadb(): void
    {
        foreach ([
            'Query execution was interrupted, maximum statement execution time exceeded',   // MySQL 8
            'Query execution was interrupted (max_statement_time exceeded)',                // MariaDB
            'SQLSTATE[70100]: Query execution was interrupted',
        ] as $msg) {
            $this->assertTrue(ReportController::isTimeout(new \RuntimeException($msg)), $msg);
        }
        $this->assertFalse(ReportController::isTimeout(new \RuntimeException('Syntax error or access violation')));
    }

    /* ── Postcondición: los reportes solo leen ── */

    public function test_the_reports_do_not_modify_any_data(): void
    {
        $p = $this->program($this->faculty('F'), 'P');
        $s = $this->seedbed('S', [$p]);
        $this->request($s, 'PENDIENTE'); $this->member($s, $p, 'PR');
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));
        $before = [DB::table('requests')->count(), DB::table('seedbed_members')->count(), DB::table('audits')->count()];

        $this->getJson('/api/reports')->assertOk();
        $this->get('/api/reports/export?report=top_areas')->streamedContent();

        $this->assertSame($before, [DB::table('requests')->count(), DB::table('seedbed_members')->count(), DB::table('audits')->count()]);
    }
}
