<?php

namespace Tests\Feature\Audit;

use Tests\TestCase;
use App\Models\User;
use App\Models\Audit;
use App\Models\Faculty;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF14 — Auditoría automática de acciones (CU14)
 */
class AuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_audits(): void
    {
        $response = $this->getJson('/api/audits');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_audits(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/audits');
        $response->assertStatus(200)->assertJsonStructure(['audits']);
    }

    public function test_creating_a_faculty_generates_audit_record(): void
    {
        $user = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        Sanctum::actingAs($user);
        $this->postJson('/api/faculties', ['name' => 'Facultad Auditada', 'status' => 'ACTIVO']);
        $this->assertDatabaseHas('audits', [
            'table_name' => 'faculties',
            'action'     => 'CREATE',
        ]);
    }

    public function test_updating_a_faculty_generates_audit_record(): void
    {
        $user    = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        Sanctum::actingAs($user);
        $faculty = Faculty::create(['name' => 'Original', 'status' => 'ACTIVO']);
        $this->putJson("/api/faculties/{$faculty->id}", ['name' => 'Modificada', 'status' => 'ACTIVO']);
        $this->assertDatabaseHas('audits', [
            'table_name' => 'faculties',
            'action'     => 'UPDATE',
        ]);
    }

    public function test_audit_records_contain_all_registered_actions(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        Audit::create(['user_id' => null, 'action' => 'CREATE', 'table_name' => 'faculties', 'record_id' => 1]);
        Audit::create(['user_id' => null, 'action' => 'UPDATE', 'table_name' => 'faculties', 'record_id' => 1]);
        $response = $this->getJson('/api/audits');
        $response->assertStatus(200);
        $audits    = collect($response->json('audits'));
        $actions   = $audits->where('table_name', 'faculties')->pluck('action')->all();
        $this->assertContains('CREATE', $actions);
        $this->assertContains('UPDATE', $actions);
    }

    public function test_audit_endpoint_returns_correct_structure(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        Audit::create(['user_id' => null, 'action' => 'CREATE', 'table_name' => 'users', 'record_id' => 1]);
        $response = $this->getJson('/api/audits');
        $response->assertStatus(200)->assertJsonStructure([
            'audits' => [['id', 'action', 'table_name', 'record_id', 'created_at']],
        ]);
    }
}
