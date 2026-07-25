<?php

namespace Tests\Feature\Auth;

use Tests\TestCase;

use App\Models\User;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Solicitar recuperación contraseña.
     */
    public function test_user_can_request_password_reset(): void
    {
        User::factory()->create([

            'email' => 'admin@test.com'

        ]);

        $response = $this->postJson(

            '/api/forgot-password',

            [
                'email' => 'admin@test.com'
            ]
        );

        $response->assertStatus(200);
    }

    /**
     * Reset requiere token válido.
     */
    public function test_password_reset_requires_valid_token(): void
    {
        $response = $this->postJson(

            '/api/reset-password',

            [

                'email' => 'admin@test.com',

                'password' => '123456',

                'password_confirmation' => '123456',

                'token' => 'token-falso'

            ]
        );

        $response->assertStatus(422);
    }
}