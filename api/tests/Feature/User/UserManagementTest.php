<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ADMIN_SISTEMA puede acceder a /api/users
     */
    public function test_admin_can_access_users_endpoint(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA',

            'status' => 'ACTIVO',

        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'users'

            ]);
    }

    /**
     * Usuario no autorizado no puede acceder.
     */
    public function test_non_admin_cannot_access_users_endpoint(): void
    {
        $student = User::factory()->create([

            'role' => 'ESTUDIANTE',

            'status' => 'ACTIVO',

        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(403)
            ->assertJson([

                'message' => 'No autorizado para realizar esta acción'

            ]);
    }

    /**
     * Cambio de estado sin eliminación física.
     */
    public function test_user_status_can_be_toggled_without_deleting_user(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA',

            'status' => 'ACTIVO',

        ]);

        $user = User::factory()->create([

            'status' => 'ACTIVO',

        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson(

            "/api/users/{$user->id}/toggle-status"
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [

            'id' => $user->id

        ]);

        $this->assertDatabaseMissing('users', [

            'id' => $user->id,
            'status' => 'ACTIVO'

        ]);
    }

    /**
     * Lider semillero puede leer la lista de usuarios (solo lectura).
     */
    public function test_leader_can_read_users_list(): void
    {
        $leader = User::factory()->create([

            'role' => 'LIDER_SEMILLERO'

        ]);

        Sanctum::actingAs($leader);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'users'

            ]);
    }


    /**
     * Administrativo puede leer la lista de usuarios (solo lectura).
     */
    public function test_administrative_can_read_users_list(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMINISTRATIVO'

        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'users'

            ]);
    }


}