<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RNF05 / RN02 – todo usuario del panel web (no ESTUDIANTE) se crea con la
 * referencia de la autorización escrita del área administrativa.
 */
class RNF05AuthorizationReferenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
    }

    public function test_panel_role_requires_authorization_reference(): void
    {
        Notification::fake();
        $this->postJson('/api/users', ['name' => 'N', 'email' => 'n@ut.edu.co', 'role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['authorization_reference']);

        $this->postJson('/api/users', [
            'name' => 'N', 'email' => 'n@ut.edu.co', 'role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO',
            'authorization_reference' => 'Oficio 045 de 2026',
        ])->assertCreated()->assertJsonPath('user.authorization_reference', 'Oficio 045 de 2026');
    }

    /* ESTUDIANTE usa la PWA, no el panel web: no se le exige */
    public function test_estudiante_does_not_require_authorization_reference(): void
    {
        Notification::fake();
        $this->postJson('/api/users', ['name' => 'N', 'email' => 'e@ut.edu.co', 'role' => 'ESTUDIANTE', 'status' => 'ACTIVO'])
            ->assertCreated();
    }

    public function test_editing_a_panel_user_still_requires_the_reference(): void
    {
        $u = User::factory()->create(['role' => 'ADMINISTRATIVO', 'authorization_reference' => 'Oficio 1']);
        $this->putJson("/api/users/{$u->id}", ['name' => $u->name, 'email' => $u->email, 'role' => 'ADMINISTRATIVO', 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['authorization_reference']);
        $this->putJson("/api/users/{$u->id}", ['name' => $u->name, 'email' => $u->email, 'role' => 'ADMINISTRATIVO', 'status' => 'ACTIVO', 'authorization_reference' => 'Oficio 2'])
            ->assertOk()->assertJsonPath('user.authorization_reference', 'Oficio 2');
    }

    /* Carga masiva: también aplica si la fila no es ESTUDIANTE */
    public function test_bulk_import_requires_authorization_reference_for_non_student_roles(): void
    {
        Notification::fake();
        $resp = $this->postJson('/api/users/import', ['users' => [
            ['name' => 'A', 'email' => 'a@ut.edu.co', 'role' => 'ESTUDIANTE'],
            ['name' => 'B', 'email' => 'b@ut.edu.co', 'role' => 'LIDER_SEMILLERO'],
            ['name' => 'C', 'email' => 'c@ut.edu.co', 'role' => 'LIDER_SEMILLERO', 'authorization_reference' => 'Oficio 3'],
        ]])->assertOk();

        $this->assertTrue($resp->json('resultados.0.success'));
        $this->assertFalse($resp->json('resultados.1.success'));
        $this->assertTrue($resp->json('resultados.2.success'));
    }
}
