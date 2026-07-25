<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

use App\Models\User;

use Illuminate\Support\Facades\Password;

use Illuminate\Foundation\Testing\RefreshDatabase;

class PasswordResetEndToEndTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Reset password end-to-end real.
     */
    public function test_user_can_reset_password_end_to_end(): void
    {
        $user = User::factory()->create([

            'email' => 'admin@test.com',

            'password' => bcrypt('123456'),

        ]);

        /*
        |--------------------------------------------------------------------------
        | Crear token reset real
        |--------------------------------------------------------------------------
        */

        $token = Password::createToken($user);

        /*
        |--------------------------------------------------------------------------
        | Ejecutar reset password
        |--------------------------------------------------------------------------
        */

        $response = $this->postJson(

            '/api/reset-password',

            [

                'email' => 'admin@test.com',

                'token' => $token,

                'password' => 'nueva123',

                'password_confirmation' => 'nueva123'

            ]
        );

        $response->assertStatus(200);

        /*
        |--------------------------------------------------------------------------
        | Login nueva contraseña
        |--------------------------------------------------------------------------
        */

        $login = $this->postJson(

            '/api/login',

            [

                'email' => 'admin@test.com',

                'password' => 'nueva123'

            ]
        );

        $login
            ->assertStatus(200)
            ->assertJsonStructure([

                'token'

            ]);
    }
}