<?php

namespace Tests\Feature\Cat;

use Tests\TestCase;
use App\Models\User;
use App\Models\Cat;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF04 — Gestión de CAT (CU04)
 */
class CatCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_cannot_access_cats(): void
    {
        $response = $this->getJson('/api/cats');
        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_cats(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->getJson('/api/cats');
        $response->assertStatus(200)->assertJsonStructure(['cats']);
    }

    public function test_authenticated_user_can_create_cat(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/cats', [
            'name' => 'CAT Bucaramanga',
            'code' => 'CAT-BGA',
        ]);
        $response->assertStatus(201);
        $this->assertDatabaseHas('cats', ['code' => 'CAT-BGA']);
    }

    public function test_cat_create_requires_name_and_code(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->postJson('/api/cats', []);
        $response->assertStatus(422)->assertJsonValidationErrors(['name', 'code']);
    }

    public function test_cat_code_must_be_unique(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        Cat::create(['name' => 'Primero', 'code' => 'CAT-001', 'status' => 'ACTIVO']);
        $response = $this->postJson('/api/cats', ['name' => 'Segundo', 'code' => 'CAT-001']);
        $response->assertStatus(422);
    }

    public function test_authenticated_user_can_update_cat(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $cat      = Cat::create(['name' => 'Original', 'code' => 'CAT-OR', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/cats/{$cat->id}", [
            'name' => 'Actualizado',
            'code' => 'CAT-OR',
        ]);
        $response->assertStatus(200);
        $this->assertDatabaseHas('cats', ['id' => $cat->id, 'name' => 'Actualizado']);
    }

    public function test_cat_update_returns_404_for_missing(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $response = $this->putJson('/api/cats/9999', ['name' => 'X', 'code' => 'Y']);
        $response->assertStatus(404);
    }

    public function test_cat_status_can_be_toggled_without_deletion(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $cat      = Cat::create(['name' => 'Test', 'code' => 'CAT-TST', 'status' => 'ACTIVO']);
        $response = $this->putJson("/api/cats/{$cat->id}/toggle-status");
        $response->assertStatus(200);
        $this->assertDatabaseHas('cats', ['id' => $cat->id, 'status' => 'INACTIVO']);
        $this->assertDatabaseHas('cats', ['id' => $cat->id]);
    }
}
