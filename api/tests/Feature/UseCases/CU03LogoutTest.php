<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU03 – Cerrar sesión (RF01, RNF03).
 */
class CU03LogoutTest extends TestCase
{
    use RefreshDatabase;

    /* Paso 2: se revoca el token del dispositivo y queda en la auditoría */
    public function test_logout_revokes_this_token_and_audits(): void
    {
        $u = User::factory()->create();
        $mine  = $u->createToken('pwa', ['*'], now()->addHours(8))->plainTextToken;
        $other = $u->createToken('web', ['*'], now()->addHours(8))->plainTextToken;

        $this->withToken($mine)->postJson('/api/logout')->assertOk();
        $this->assertDatabaseHas('audits', ['action' => 'LOGOUT', 'user_id' => $u->id]);
        $this->assertSame(1, $u->tokens()->count());   /* el otro dispositivo sigue */

        $this->app['auth']->forgetGuards();
        $this->withToken($mine)->getJson('/api/me')->assertStatus(401);
        $this->app['auth']->forgetGuards();
        $this->withToken($other)->getJson('/api/me')->assertOk();
    }

    /* E1: el token que se revoca «en la siguiente conexión» ya vencido no causa error */
    public function test_revoking_an_expired_or_revoked_token_answers_401(): void
    {
        $u = User::factory()->create();
        $t = $u->createToken('pwa', ['*'], now()->subMinute())->plainTextToken;
        $this->withToken($t)->postJson('/api/logout')->assertStatus(401);
    }

    /* Un estudiante sin autorización de datos puede cerrar sesión (RF16 la deja libre) */
    public function test_student_without_consent_can_logout(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE', 'data_consent_at' => null]);
        $t = $u->createToken('pwa', ['*'], now()->addHour())->plainTextToken;
        $this->withToken($t)->postJson('/api/logout')->assertOk();
    }
}
