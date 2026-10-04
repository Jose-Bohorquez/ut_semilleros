<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\Area;
use App\Models\Audit;
use App\Models\Coordinator;
use App\Models\Faculty;
use App\Models\Notificacion;
use App\Models\Program;
use App\Models\Project;
use App\Models\Proposal;
use App\Models\Seedbed;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU29 — Registrar auditoría (RF14, RN07, RNF03). Cada test cita el paso, alterno o excepción
 * de la especificación que demuestra.
 */
class CU29AuditTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'ADMIN_SISTEMA', 'status' => 'ACTIVO']);
    }

    private function last(string $table, ?string $action = null): ?Audit
    {
        return Audit::where('table_name', $table)
            ->when($action, fn ($q) => $q->where('action', $action))
            ->latest('id')->first();
    }

    /* ── Pasos 2-3: valores anteriores y nuevos de los campos modificados ── */

    public function test_update_stores_old_and_new_values_of_only_the_changed_fields(): void
    {
        Sanctum::actingAs($admin = $this->admin());
        $f = Faculty::create(['name' => 'Original', 'code' => 'FO', 'status' => 'ACTIVO']);

        $f->update(['name' => 'Modificada']);

        $row = $this->last('faculties', 'UPDATE');
        $this->assertSame(['name' => 'Original'], $row->old_values);
        $this->assertSame(['name' => 'Modificada'], $row->new_values);
        $this->assertSame($f->id, (int) $row->record_id);
        $this->assertSame($admin->id, (int) $row->user_id);
    }

    public function test_create_stores_the_new_values_and_no_old_values(): void
    {
        Sanctum::actingAs($this->admin());
        $f = Faculty::create(['name' => 'Nueva', 'code' => 'FN', 'status' => 'ACTIVO']);

        $row = $this->last('faculties', 'CREATE');
        $this->assertNull($row->old_values);
        $this->assertSame('Nueva', $row->new_values['name']);
        $this->assertSame('FN', $row->new_values['code']);
        $this->assertSame($f->id, (int) $row->record_id);
    }

    /* Paso 2: «excluyendo contraseñas y tokens» */

    public function test_passwords_and_tokens_never_reach_the_audit_values(): void
    {
        Sanctum::actingAs($this->admin());
        $hash = Hash::make('Secreta#2026');
        $u = User::factory()->create(['password' => $hash, 'remember_token' => 'tok-abc-123']);
        $u->update(['password' => Hash::make('Otra#2027'), 'name' => 'Cambia Nombre']);

        foreach (Audit::where('table_name', 'users')->get() as $row) {
            $json = json_encode([$row->old_values, $row->new_values]);
            $this->assertStringNotContainsString($hash, $json);
            $this->assertStringNotContainsString('tok-abc-123', $json);
            foreach ((array) $row->new_values + (array) $row->old_values as $key => $_) {
                $this->assertDoesNotMatchRegularExpression('/password|token|google_id|profile_photo/i', $key);
            }
        }
        $this->assertSame('Cambia Nombre', $this->last('users', 'UPDATE')->new_values['name']);
    }

    /* RNF03/RNF12: los campos cifrados con APP_KEY no quedan en claro en la auditoría */

    public function test_encrypted_fields_are_masked_not_stored_in_clear(): void
    {
        Sanctum::actingAs($this->admin());
        $c = Coordinator::create([
            'name' => 'Coord', 'document' => '123', 'email' => 'c@ut.edu.co', 'phone' => '3001112233', 'status' => 'ACTIVO',
        ]);
        $c->update(['phone' => '3105556677']);

        $create = $this->last('coordinators', 'CREATE');
        $update = $this->last('coordinators', 'UPDATE');
        $this->assertSame('[cifrado]', $create->new_values['phone']);
        $this->assertSame('[cifrado]', $update->new_values['phone']);
        $this->assertSame('[cifrado]', $update->old_values['phone']);
        $all = json_encode(Audit::where('table_name', 'coordinators')->get()->toArray());
        $this->assertStringNotContainsString('3001112233', $all);
        $this->assertStringNotContainsString('3105556677', $all);
    }

    /* ── «cambio de estado» como acción propia (flujo, paso 1) ── */

    public function test_status_change_is_its_own_action(): void
    {
        Sanctum::actingAs($this->admin());
        $f = Faculty::create(['name' => 'Estado', 'code' => 'FE', 'status' => 'ACTIVO']);

        $f->update(['name' => 'Solo nombre']);
        $this->assertSame('UPDATE', $this->last('faculties')->action);

        $f->update(['status' => 'INACTIVO']);
        $row = $this->last('faculties');
        $this->assertSame('STATUS_CHANGE', $row->action);
        $this->assertSame(['status' => 'ACTIVO'], $row->old_values);
        $this->assertSame(['status' => 'INACTIVO'], $row->new_values);
    }

    /* ── IP (descripción del CU y paso 3) ── */

    public function test_the_client_ip_is_recorded(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($u);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.7'])
            ->putJson('/api/profile', ['phone' => '3101234567'])->assertOk();

        $this->assertSame('203.0.113.7', $this->last('users', 'UPDATE')->ip_address);
    }

    /* ── A1: inicio de sesión — solo usuario, acción, IP y fecha ── */

    public function test_login_records_only_user_action_ip_and_date(): void
    {
        $u = User::factory()->create(['role' => 'ADMINISTRATIVO', 'status' => 'ACTIVO', 'password' => Hash::make('Clave#Segura1')]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.9'])
            ->postJson('/api/login', ['email' => $u->email, 'password' => 'Clave#Segura1'])->assertOk();

        $row = Audit::where('action', 'LOGIN')->latest('id')->first();
        $this->assertSame($u->id, (int) $row->user_id);
        $this->assertSame('198.51.100.9', $row->ip_address);
        $this->assertNull($row->old_values);
        $this->assertNull($row->new_values);
        $this->assertNotNull($row->created_at);
    }

    public function test_updating_only_last_login_at_does_not_create_noise(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE']);
        $before = Audit::count();

        $u->forceFill(['last_login_at' => now()])->save();

        $this->assertSame($before, Audit::count());
    }

    /* ── Postcondición: «no modificable» (RN07) ── */

    public function test_audit_records_cannot_be_updated_or_deleted(): void
    {
        $row = Audit::create(['user_id' => null, 'action' => 'UPDATE', 'table_name' => 'faculties', 'record_id' => 1]);

        try { $row->update(['action' => 'DELETE']); $this->fail('Debía lanzar.'); }
        catch (\LogicException $e) { $this->assertStringContainsString('no se pueden modificar', $e->getMessage()); }

        try { $row->delete(); $this->fail('Debía lanzar.'); }
        catch (\LogicException $e) { $this->assertStringContainsString('no se pueden eliminar', $e->getMessage()); }

        $this->assertSame('UPDATE', $row->fresh()->action);
        $this->assertNull($row->fresh()->updated_at ?? null);
    }

    /* ── E1: falla el registro → log técnico + alerta al Administrador + no se revierte ── */

    public function test_e1_audit_failure_does_not_revert_the_operation_and_alerts_the_admin(): void
    {
        $admin = $this->admin();
        Sanctum::actingAs($admin);
        $f = Faculty::create(['name' => 'Antes', 'code' => 'FX', 'status' => 'ACTIVO']);
        Cache::forget('audit:failure-alert');
        Log::spy();

        Schema::drop('audits');   // la auditoría deja de poder escribir

        $this->putJson("/api/faculties/{$f->id}", ['code' => 'FX', 'name' => 'Despues', 'status' => 'ACTIVO'])->assertOk();

        $this->assertSame('Despues', $f->fresh()->name, 'La operación principal NO se revierte.');
        Log::shouldHaveReceived('error')->withArgs(fn ($msg) => str_contains((string) $msg, '[AUDIT]'))->atLeast()->once();

        $alert = Notificacion::where('title', 'Falla en el registro de auditoría')->get();
        $this->assertCount(1, $alert);
        $this->assertSame('ROLE', $alert[0]->target_type);
        $this->assertSame('ADMIN_SISTEMA', $alert[0]->target_value);
    }

    public function test_e1_repeated_failures_send_a_single_alert_per_window(): void
    {
        Sanctum::actingAs($this->admin());
        $f = Faculty::create(['name' => 'A', 'code' => 'FY', 'status' => 'ACTIVO']);
        Cache::forget('audit:failure-alert');
        Schema::drop('audits');

        $f->update(['name' => 'B']);
        $f->update(['name' => 'C']);
        $f->update(['name' => 'D']);

        $this->assertSame('D', $f->fresh()->name);
        $this->assertSame(1, Notificacion::where('title', 'Falla en el registro de auditoría')->count());
    }

    public function test_login_is_the_documented_exception_it_fails_closed_if_it_cannot_be_audited(): void
    {
        $u = User::factory()->create(['role' => 'ADMINISTRATIVO', 'status' => 'ACTIVO', 'password' => Hash::make('Clave#Segura1')]);
        Schema::drop('audits');

        $res = $this->postJson('/api/login', ['email' => $u->email, 'password' => 'Clave#Segura1']);

        $this->assertNotSame(200, $res->status());
        $this->assertSame(0, \Laravel\Sanctum\PersonalAccessToken::count(), 'Sin auditoría no se emite token (decisión de CU01).');
    }

    /* ── Tablas pivote: el cambio queda como UPDATE del documento padre ── */

    public function test_changing_the_areas_of_a_seedbed_is_audited(): void
    {
        Sanctum::actingAs($this->admin());
        $faculty = Faculty::create(['name' => 'F', 'code' => 'FP', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'P', 'code' => 'PP', 'faculty_id' => $faculty->id, 'type' => 'PREGRADO', 'status' => 'ACTIVO']);
        $a1 = Area::create(['name' => 'A1', 'code' => 'A1', 'status' => 'ACTIVO']);
        $a2 = Area::create(['name' => 'A2', 'code' => 'A2', 'status' => 'ACTIVO']);
        $s = Seedbed::create(['name' => 'Sem', 'status' => 'ACTIVO']);

        $this->putJson("/api/seedbeds/{$s->id}", [
            'code' => 'SB-1', 'name' => 'Sem', 'objetivo_general' => 'Objetivo general de prueba',
            'authorization_reference' => 'Acta 1', 'programs' => [$program->id], 'areas' => [$a1->id],
        ])->assertOk();
        $this->putJson("/api/seedbeds/{$s->id}", [
            'code' => 'SB-1', 'name' => 'Sem', 'objetivo_general' => 'Objetivo general de prueba',
            'authorization_reference' => 'Acta 1', 'programs' => [$program->id], 'areas' => [$a1->id, $a2->id],
        ])->assertOk();

        $row = Audit::where('table_name', 'seedbeds')->where('action', 'UPDATE')->get()
            ->first(fn ($r) => isset($r->new_values['areas']) && count($r->new_values['areas']) === 2);
        $this->assertNotNull($row, 'Debe existir el cambio de áreas con la lista antes y después.');
        $this->assertSame([$a1->id], $row->old_values['areas']);
        $this->assertEqualsCanonicalizing([$a1->id, $a2->id], $row->new_values['areas']);
    }

    public function test_unchanged_pivot_does_not_create_a_row(): void
    {
        Sanctum::actingAs($this->admin());
        $p = Proposal::create(['user_id' => User::factory()->create()->id, 'title' => 'T', 'description' => 'x', 'status' => 'RECIBIDA']);
        $area = Area::create(['name' => 'A', 'code' => 'AX', 'status' => 'ACTIVO']);
        \App\Support\AuditTrail::pivot($p, 'areas', [$area->id], [$area->id]);

        $this->assertNull(Audit::where('table_name', 'proposals')->get()->first(fn ($r) => isset($r->new_values['areas'])));
    }

    public function test_project_member_changes_are_audited(): void
    {
        Sanctum::actingAs($this->admin());
        $seedbed = Seedbed::create(['name' => 'S', 'status' => 'ACTIVO']);
        $project = Project::create(['seedbed_id' => $seedbed->id, 'title' => 'Proyecto', 'description' => 'd', 'status' => 'ACTIVO']);
        $member = User::factory()->create(['role' => 'ESTUDIANTE']);

        $this->postJson("/api/projects/{$project->id}/members", ['user_id' => $member->id, 'role' => 'ESTUDIANTE'])->assertCreated();
        $this->deleteJson("/api/projects/{$project->id}/members/{$member->id}")->assertOk();

        $rows = Audit::where('table_name', 'projects')->whereNotNull('new_values')->orderBy('id')->get()
            ->filter(fn ($r) => isset($r->new_values['members']))->values();
        $this->assertCount(2, $rows);
        $this->assertSame([$member->id], $rows[0]->new_values['members']);
        $this->assertSame([], $rows[1]->new_values['members']);
    }

    /* ── Configuración de SIA ── */

    public function test_sia_limit_changes_are_audited_with_only_the_changed_limits(): void
    {
        Sanctum::actingAs($this->admin());
        $this->putJson('/api/sia/admin/settings', ['user_per_day' => 77])->assertOk();

        $row = $this->last('sia_settings', 'UPDATE');
        $this->assertSame(['user_per_day' => '60'], array_map('strval', $row->old_values));
        $this->assertSame(['user_per_day' => '77'], array_map('strval', $row->new_values));
    }

    /* ── CU30 depende de esto: la consulta devuelve los valores y tolera filas antiguas ── */

    public function test_the_audit_endpoint_returns_values_and_tolerates_legacy_rows(): void
    {
        Sanctum::actingAs($this->admin());
        Audit::create(['user_id' => null, 'action' => 'CREATE', 'table_name' => 'faculties', 'record_id' => 1]); // fila antigua
        Faculty::create(['name' => 'Con valores', 'code' => 'FV', 'status' => 'ACTIVO']);

        $audits = collect($this->getJson('/api/audits')->assertOk()->json('audits'));
        $legacy = $audits->first(fn ($a) => $a['record_id'] === 1 && $a['new_values'] === null);
        $this->assertNotNull($legacy);
        $this->assertTrue($audits->contains(fn ($a) => ($a['new_values']['name'] ?? null) === 'Con valores'));
    }
}
