<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\Audit;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU30 — Consultar auditoría (RF14, RN07, RNF03). Cada test cita el paso, alterno o excepción
 * de la especificación que demuestra.
 */
class CU30AuditQueryTest extends TestCase
{
    use RefreshDatabase;

    /** Crear usuarios genera filas de auditoría (CU29); se descartan para contar solo lo que cada test inserta. */
    private function clean(): void
    {
        DB::table('audits')->delete();   // consulta directa: el modelo Audit es inmutable (RN07)
    }

    private function admin(): User
    {
        $u = User::factory()->create(['role' => 'ADMIN_SISTEMA', 'status' => 'ACTIVO']);
        $this->clean();
        return $u;
    }

    /** Inserta una fila con fecha explícita (UTC); la auditoría es inmutable y `created_at` no es asignable. */
    private function audit(array $attrs = []): int
    {
        return DB::table('audits')->insertGetId(array_merge([
            'user_id'    => null,
            'action'     => 'UPDATE',
            'table_name' => 'faculties',
            'record_id'  => 1,
            'old_values' => null,
            'new_values' => null,
            'ip_address' => '203.0.113.5',
            'created_at' => now()->toDateTimeString(),
        ], array_map(fn ($v) => is_array($v) ? json_encode($v) : $v, $attrs)));
    }

    /* ── E2: solo el Administrador ── */

    public function test_unauthenticated_gets_401(): void
    {
        foreach (['', '/summary', '/options', '/export', '/1'] as $suffix) {
            $this->getJson("/api/audits$suffix")->assertStatus(401);
        }
    }

    public function test_e2_every_other_role_gets_403_on_every_endpoint(): void
    {
        $id = $this->audit();
        foreach (['ADMINISTRATIVO', 'LIDER_SEMILLERO', 'ESTUDIANTE'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            foreach (['', '/summary', '/options', '/export', "/$id"] as $suffix) {
                $this->getJson("/api/audits$suffix")->assertStatus(403);
            }
        }
    }

    /* ── Paso 2: los registros más recientes con fecha, usuario, acción, colección y documento ── */

    public function test_lists_newest_first_with_date_user_action_collection_and_document(): void
    {
        $actor = $this->admin();
        Sanctum::actingAs($actor);
        $old = $this->audit(['created_at' => '2026-01-01 10:00:00', 'record_id' => 11]);
        $new = $this->audit(['created_at' => '2026-02-01 10:00:00', 'record_id' => 22, 'user_id' => $actor->id]);

        $rows = $this->getJson('/api/audits')->assertOk()->json('audits');

        $this->assertSame([$new, $old], array_column($rows, 'id'));
        $this->assertSame($actor->name, $rows[0]['user']['name']);
        foreach (['created_at', 'created_at_local', 'action', 'table_name', 'record_id', 'ip_address'] as $key) {
            $this->assertArrayHasKey($key, $rows[0]);
        }
    }

    public function test_dates_are_shown_in_bogota_time(): void
    {
        Sanctum::actingAs($this->admin());
        $this->audit(['created_at' => '2026-03-10 15:30:00']);   // UTC

        $this->assertSame('2026-03-10 10:30:00', $this->getJson('/api/audits')->json('audits.0.created_at_local'));
    }

    /* ── Paso 4: paginados ── */

    public function test_results_are_paginated_by_the_server(): void
    {
        Sanctum::actingAs($this->admin());
        foreach (range(1, 7) as $i) {
            $this->audit(['record_id' => $i]);
        }

        $p1 = $this->getJson('/api/audits?per_page=3&page=1')->assertOk();
        $p3 = $this->getJson('/api/audits?per_page=3&page=3')->assertOk();

        $this->assertCount(3, $p1->json('audits'));
        $this->assertSame(['current_page' => 1, 'last_page' => 3, 'per_page' => 3, 'total' => 7], $p1->json('meta'));
        $this->assertCount(1, $p3->json('audits'));
    }

    public function test_default_page_size_is_25_and_the_maximum_is_100(): void
    {
        Sanctum::actingAs($this->admin());
        foreach (range(1, 30) as $i) {
            $this->audit(['record_id' => $i]);
        }

        $this->assertCount(25, $this->getJson('/api/audits')->json('audits'));
        $this->getJson('/api/audits?per_page=101')->assertStatus(422)->assertJsonValidationErrors(['per_page']);
    }

    /* ── Paso 3: filtros por usuario, colección, acción y rango de fechas ── */

    public function test_filters_by_user_collection_and_action_and_they_combine(): void
    {
        $actor = $this->admin();
        $other = User::factory()->create();
        $this->clean();
        Sanctum::actingAs($actor);
        $a = $this->audit(['user_id' => $actor->id, 'table_name' => 'users', 'action' => 'CREATE']);
        $this->audit(['user_id' => $other->id, 'table_name' => 'users', 'action' => 'CREATE']);
        $this->audit(['user_id' => $actor->id, 'table_name' => 'seedbeds', 'action' => 'CREATE']);
        $this->audit(['user_id' => $actor->id, 'table_name' => 'users', 'action' => 'UPDATE']);

        $ids = fn ($q) => array_column($this->getJson("/api/audits?$q")->assertOk()->json('audits'), 'id');

        $this->assertCount(3, $ids("user_id={$actor->id}"));
        $this->assertCount(3, $ids('table=users'));
        $this->assertCount(3, $ids('action=CREATE'));
        $this->assertSame([$a], $ids("user_id={$actor->id}&table=users&action=CREATE"));
    }

    /** Los días «desde/hasta» son días completos de Bogotá (UTC-5), no de UTC. */
    public function test_date_range_uses_whole_days_in_bogota_time(): void
    {
        Sanctum::actingAs($this->admin());
        $beforeStart = $this->audit(['created_at' => '2026-03-10 04:59:59']);   // 09-mar 23:59:59 en Bogotá
        $startOfDay  = $this->audit(['created_at' => '2026-03-10 05:00:00']);   // 10-mar 00:00:00
        $endOfDay    = $this->audit(['created_at' => '2026-03-11 04:59:59']);   // 10-mar 23:59:59
        $afterEnd    = $this->audit(['created_at' => '2026-03-11 05:00:00']);   // 11-mar 00:00:00

        $ids = array_column($this->getJson('/api/audits?from=2026-03-10&to=2026-03-10')->assertOk()->json('audits'), 'id');

        $this->assertEqualsCanonicalizing([$startOfDay, $endOfDay], $ids);
        $this->assertNotContains($beforeStart, $ids);
        $this->assertNotContains($afterEnd, $ids);
    }

    public function test_invalid_dates_and_inverted_ranges_are_rejected(): void
    {
        Sanctum::actingAs($this->admin());
        $this->getJson('/api/audits?from=10/03/2026')->assertStatus(422)->assertJsonValidationErrors(['from']);
        $this->getJson('/api/audits?from=2026-03-10&to=2026-03-01')->assertStatus(422)->assertJsonValidationErrors(['to']);
    }

    /* ── E1: sin resultados ── */

    public function test_e1_no_results_returns_the_exact_message(): void
    {
        Sanctum::actingAs($this->admin());
        $this->audit(['table_name' => 'users']);

        $res = $this->getJson('/api/audits?table=inexistente')->assertOk();

        $this->assertSame([], $res->json('audits'));
        $this->assertSame(0, $res->json('meta.total'));
        $this->assertSame('No hay registros para los filtros seleccionados', $res->json('message'));
    }

    public function test_message_is_null_when_there_are_results(): void
    {
        Sanctum::actingAs($this->admin());
        $this->audit();
        $this->assertNull($this->getJson('/api/audits')->json('message'));
    }

    /* ── Pasos 5-6: «Ver» con la comparación de valores anteriores y nuevos ── */

    public function test_show_returns_a_field_by_field_comparison(): void
    {
        Sanctum::actingAs($this->admin());
        $id = $this->audit([
            'old_values' => ['name' => 'Antes', 'status' => 'ACTIVO'],
            'new_values' => ['name' => 'Despues', 'status' => 'INACTIVO'],
            'action'     => 'STATUS_CHANGE',
        ]);

        $audit = $this->getJson("/api/audits/$id")->assertOk()->json('audit');

        $this->assertSame('STATUS_CHANGE', $audit['action']);
        $this->assertEqualsCanonicalizing([
            ['field' => 'name',   'old' => 'Antes',  'new' => 'Despues'],
            ['field' => 'status', 'old' => 'ACTIVO', 'new' => 'INACTIVO'],
        ], $audit['changes']);
    }

    public function test_show_handles_creates_deletes_pivots_and_legacy_rows(): void
    {
        Sanctum::actingAs($this->admin());
        $create = $this->audit(['action' => 'CREATE', 'new_values' => ['name' => 'Nueva']]);
        $pivot  = $this->audit(['new_values' => ['areas' => [1, 2]], 'old_values' => ['areas' => [1]]]);
        $legacy = $this->audit();   // anterior a CU29: sin valores

        $this->assertSame([['field' => 'name', 'old' => null, 'new' => 'Nueva']], $this->getJson("/api/audits/$create")->json('audit.changes'));
        $this->assertSame([['field' => 'areas', 'old' => [1], 'new' => [1, 2]]], $this->getJson("/api/audits/$pivot")->json('audit.changes'));
        $this->assertSame([], $this->getJson("/api/audits/$legacy")->json('audit.changes'));
    }

    public function test_show_returns_404_for_a_missing_record(): void
    {
        Sanctum::actingAs($this->admin());
        $this->getJson('/api/audits/999999')->assertStatus(404);
    }

    /* ── A1: exportar a CSV los resultados filtrados ── */

    public function test_a1_export_returns_a_csv_with_only_the_filtered_rows(): void
    {
        Sanctum::actingAs($this->admin());
        $this->audit(['table_name' => 'users', 'record_id' => 111, 'new_values' => ['name' => 'Ana']]);
        $this->audit(['table_name' => 'seedbeds', 'record_id' => 222]);

        $res = $this->get('/api/audits/export?table=users', ['Accept' => 'text/csv'])->assertOk();
        $csv = $res->streamedContent();

        $this->assertStringContainsString('text/csv', $res->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', $res->headers->get('Content-Disposition'));
        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'BOM UTF-8 para que Excel lea los acentos.');
        $this->assertStringContainsString('Fecha (hora de Bogotá)', $csv);
        $this->assertStringContainsString('111', $csv);
        $this->assertStringContainsString('{""name"":""Ana""}', $csv, 'El JSON va como celda CSV, con las comillas escapadas.');
        $this->assertStringNotContainsString('222', $csv, 'Solo las filas del filtro.');
    }

    public function test_export_neutralises_csv_formula_injection(): void
    {
        $actor = User::factory()->create(['role' => 'ADMIN_SISTEMA', 'name' => '=HYPERLINK("http://malo.example","clic")']);
        $this->clean();
        Sanctum::actingAs($actor);
        $this->audit(['user_id' => $actor->id]);

        $csv = $this->get('/api/audits/export')->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
        $this->assertDoesNotMatchRegularExpression('/(^|,)"=HYPERLINK/m', $csv);
    }

    public function test_export_applies_the_same_validation_as_the_list(): void
    {
        Sanctum::actingAs($this->admin());
        $this->getJson('/api/audits/export?from=2026-03-10&to=2026-03-01')->assertStatus(422);
    }

    /* ── Soporte de la pantalla: selectores y contadores del panel ── */

    public function test_options_list_only_what_exists_in_the_audit(): void
    {
        $actor = $this->admin();
        Sanctum::actingAs($actor);
        $this->audit(['action' => 'CREATE', 'table_name' => 'users', 'user_id' => $actor->id]);
        $this->audit(['action' => 'LOGIN', 'table_name' => 'users']);

        $res = $this->getJson('/api/audits/options')->assertOk();

        $this->assertSame(['CREATE', 'LOGIN'], $res->json('actions'));
        $this->assertSame(['users'], $res->json('tables'));
        $this->assertSame([$actor->id], array_column($res->json('users'), 'id'));
    }

    public function test_summary_gives_the_totals_without_downloading_the_table(): void
    {
        $actor = $this->admin();
        Sanctum::actingAs($actor);
        $this->audit(['created_at' => now()->subDays(30)->toDateTimeString()]);
        $this->audit(['created_at' => now()->subDays(2)->toDateTimeString()]);
        $this->audit(['action' => 'LOGIN', 'table_name' => 'users', 'user_id' => $actor->id, 'created_at' => now()->toDateTimeString()]);

        $res = $this->getJson('/api/audits/summary')->assertOk();

        $this->assertSame(3, $res->json('total'));
        $this->assertSame(2, $res->json('last_7_days'));
        $this->assertSame('LOGIN', $res->json('latest.action'));
        $this->assertSame($actor->name, $res->json('latest.user.name'));
    }

    /* ── Postcondición: la consulta no modifica la auditoría ── */

    public function test_querying_the_audit_does_not_change_it(): void
    {
        Sanctum::actingAs($this->admin());
        $this->audit();
        $before = Audit::count();

        $this->getJson('/api/audits')->assertOk();
        $this->getJson('/api/audits/summary')->assertOk();
        $this->get('/api/audits/export')->streamedContent();

        $this->assertSame($before, Audit::count());
    }
}
