<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Login exitoso con usuario activo.
     */
    public function test_user_can_login_successfully(): void
    {
        $user = User::factory()->create([

            'email' => 'admin@test.com',

            'password' => bcrypt('123456'),

            'role' => 'ADMIN_SISTEMA',

            'status' => 'ACTIVO',
        ]);

        $response = $this->postJson('/api/login', [

            'email' => 'admin@test.com',

            'password' => '123456',

        ]);

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'message',
                'user',
                'token',

            ]);
    }

    /**
     * Login fallido con credenciales incorrectas.
     */
    public function test_login_fails_with_invalid_credentials(): void
    {
        User::factory()->create([

            'email' => 'admin@test.com',

            'password' => bcrypt('123456'),

        ]);

        $response = $this->postJson('/api/login', [

            'email' => 'admin@test.com',

            'password' => 'incorrecta',

        ]);

        $response
            ->assertStatus(401)
            ->assertJson([

                'message' => 'Credenciales incorrectas'

            ]);
    }

    /**
     * Ruta protegida sin autenticación.
     */
    public function test_protected_route_requires_authentication(): void
    {
        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(401)
            ->assertJson([

                'message' => 'No autenticado'

            ]);
    }

    /**
     * Logout invalida token actual.
     */
    public function test_user_can_logout_and_token_is_invalidated(): void
    {
        $user = User::factory()->create([

            'role' => 'ADMIN_SISTEMA',

            'status' => 'ACTIVO',

        ]);

        Sanctum::actingAs($user);

        $response = $this->postJson('/api/logout');

        $response
            ->assertStatus(200);

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    /**
     * Usuario inactivo no puede iniciar sesión.
     */
    public function test_inactive_user_cannot_login(): void
    {
        User::factory()->create([

            'email' => 'inactive@test.com',

            'password' => bcrypt('123456'),

            'status' => 'INACTIVO',

        ]);

        $response = $this->postJson('/api/login', [

            'email' => 'inactive@test.com',

            'password' => '123456',

        ]);

        $response
            ->assertStatus(403)
            ->assertJson([

                'message' => 'Usuario inactivo'

            ]);
    }

}