<?php

namespace Tests\Feature\User;

use Tests\TestCase;

use App\Models\User;

use Laravel\Sanctum\Sanctum;

use Illuminate\Foundation\Testing\RefreshDatabase;

class UserCrudTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Admin puede crear usuario.
     */
    public function test_admin_can_create_user(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA'

        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [

            'name' => 'Nuevo Usuario',

            'email' => 'nuevo@test.com',

            'password' => 'Nueva#Clave2026',   /* RN10 */

            'password_confirmation' => 'Nueva#Clave2026',

            'role' => 'LIDER_SEMILLERO',

            'status' => 'ACTIVO',

            'authorization_reference' => 'Oficio 001 de 2026',

        ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [

            'email' => 'nuevo@test.com'

        ]);
    }

    /**
     * No permite email duplicado.
     */
    public function test_user_email_must_be_unique(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA'

        ]);

        User::factory()->create([

            'email' => 'duplicado@test.com'

        ]);

        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [

            'name' => 'Duplicado',

            'email' => 'duplicado@test.com',

            'password' => '123456',

            'role' => 'LIDER_SEMILLERO',

            'status' => 'ACTIVO',

            'authorization_reference' => 'Oficio 002 de 2026',

        ]);

        $response->assertStatus(422);
    }

    /**
     * CU06-A4: los estudiantes no se crean uno por uno; solo por carga
     * masiva (import) o por su cuenta institucional de Google (CU02).
     */
    public function test_cannot_create_estudiante_individually(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users', [
            'name' => 'Estudiante Suelto',
            'email' => 'suelto@test.com',
            'role' => 'ESTUDIANTE',
            'status' => 'ACTIVO',
        ]);

        $response->assertStatus(422)->assertJsonValidationErrors(['role']);
        $this->assertDatabaseMissing('users', ['email' => 'suelto@test.com']);
    }

    /** La carga masiva sigue permitiendo ESTUDIANTE (Jose, 2026-09-29: no se toca). */
    public function test_bulk_import_still_allows_estudiante(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        Sanctum::actingAs($admin);

        $response = $this->postJson('/api/users/import', [
            'users' => [
                ['name' => 'Estudiante Import', 'email' => 'import@test.com', 'role' => 'ESTUDIANTE'],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('users', ['email' => 'import@test.com', 'role' => 'ESTUDIANTE']);
    }

    /**
     * Admin puede consultar usuario.
     */
    public function test_admin_can_view_single_user(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA'

        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->getJson(

            "/api/users/{$user->id}"

        );

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'user'

            ]);
    }

    /**
     * Usuario inexistente retorna 404.
     */
    public function test_show_returns_404_for_missing_user(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA'

        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson(

            '/api/users/9999'

        );

        $response->assertStatus(404);
    }

    /**
     * Admin puede actualizar usuario.
     */
    public function test_admin_can_update_user(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA'

        ]);

        $user = User::factory()->create();

        Sanctum::actingAs($admin);

        $response = $this->putJson(

            "/api/users/{$user->id}",

            [

                'name' => 'Actualizado',

                'email' => $user->email,

                'role' => $user->role,

                'status' => 'ACTIVO'

            ]
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [

            'id' => $user->id,

            'name' => 'Actualizado'

        ]);
    }
}