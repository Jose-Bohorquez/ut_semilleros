<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cat;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF04 – Gestión de Centros de Atención Tutorial (CAT). RN08: código único.
 * RN01: sin eliminación. Criterio: todo CAT tiene al menos un teléfono.
 */
class RF04CatTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
    }

    private function payload(array $o = []): array
    {
        return array_merge([
            'name' => 'CAT Ibagué', 'code' => 'cat-iba ', 'address' => 'Calle 1',
            'city' => 'Ibagué', 'email' => 'cat.ibague@ut.edu.co', 'phone1' => '6082610000',
        ], $o);
    }

    public function test_create_requires_code_and_name_and_registers_active(): void
    {
        $this->postJson('/api/cats', [])->assertStatus(422)->assertJsonValidationErrors(['name', 'code']);
        $this->postJson('/api/cats', $this->payload())->assertCreated()
            ->assertJsonPath('cat.code', 'CAT-IBA')->assertJsonPath('cat.status', 'ACTIVO');
    }

    /* RN08 */
    public function test_code_is_unique_case_insensitive(): void
    {
        $this->postJson('/api/cats', $this->payload())->assertCreated();
        $this->postJson('/api/cats', $this->payload(['code' => 'cat-iba']))
            ->assertStatus(422)->assertJsonValidationErrors(['code' => 'Ya existe un CAT con ese código.']);
    }

    /* Criterio: todo CAT tiene al menos un teléfono */
    public function test_at_least_one_phone_is_required(): void
    {
        $this->postJson('/api/cats', $this->payload(['phone1' => null]))
            ->assertStatus(422)->assertJsonValidationErrors(['phone1' => 'El CAT debe tener al menos un teléfono.']);
        $this->postJson('/api/cats', $this->payload(['phone1' => null, 'phone2' => '3001234567']))
            ->assertCreated();
        $this->postJson('/api/cats', $this->payload(['code' => 'CAT-P3', 'phone1' => null, 'phone3' => '3001234567']))
            ->assertCreated();
    }

    /* Procesamiento: validación de formato de correo y teléfonos */
    public function test_email_and_phone_format_are_validated(): void
    {
        $this->postJson('/api/cats', $this->payload(['email' => 'no-es-correo']))
            ->assertStatus(422)->assertJsonValidationErrors(['email']);
        $this->postJson('/api/cats', $this->payload(['phone1' => 'abc-texto-no-telefono']))
            ->assertStatus(422)->assertJsonValidationErrors(['phone1']);
        $this->postJson('/api/cats', $this->payload(['phone1' => '+57 (608) 261-0000']))->assertCreated();
    }

    public function test_cats_cannot_be_deleted_and_changes_are_audited(): void
    {
        $id = $this->postJson('/api/cats', $this->payload())->json('cat.id');
        $this->deleteJson("/api/cats/{$id}")->assertStatus(405);
        $this->assertDatabaseHas('cats', ['id' => $id]);
        $this->assertDatabaseHas('audits', ['table_name' => 'cats', 'record_id' => $id]);
    }

    /* Legacy sin código/teléfono al editar: se exige código, y el teléfono si borran los 3 */
    public function test_update_keeps_own_code_and_enforces_phone_rule(): void
    {
        $c = Cat::create(['name' => 'C', 'code' => 'CAT-X', 'phone1' => '3001234567', 'status' => 'ACTIVO']);
        $this->putJson("/api/cats/{$c->id}", ['name' => 'C2', 'code' => 'CAT-X', 'phone1' => '3001234567', 'status' => 'ACTIVO'])->assertOk();
        $this->putJson("/api/cats/{$c->id}", ['name' => 'C3', 'code' => 'CAT-X', 'status' => 'ACTIVO'])
            ->assertStatus(422)->assertJsonValidationErrors(['phone1']);
    }

    public function test_read_roles_and_write_only_admin(): void
    {
        $this->postJson('/api/cats', $this->payload())->assertCreated();
        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role]));
            $this->getJson('/api/cats')->assertOk();
            $this->postJson('/api/cats', $this->payload(['code' => 'X-' . $role]))->assertStatus(403);
        }
    }
}
