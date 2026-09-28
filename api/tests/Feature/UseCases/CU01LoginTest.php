<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU01 – Iniciar sesión en el panel web (RF01, RN14, RNF03, CU29).
 * Cada test corresponde a un paso, alterno o excepción de
 * docs/especificacion/Especificacion_Requerimientos_Casos_de_Uso_SemillerosUT.md §CU01.
 */
class CU01LoginTest extends TestCase
{
    use RefreshDatabase;

    private function user(array $attrs = []): User
    {
        return User::factory()->create(array_merge([
            'email' => 'cu01@test.com', 'password' => Hash::make('Clave#2026'), 'role' => 'ADMIN_SISTEMA',
        ], $attrs));
    }

    private function login(string $password = 'Clave#2026', array $extra = [])
    {
        return $this->postJson('/api/login', array_merge(['email' => 'cu01@test.com', 'password' => $password], $extra));
    }

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('login:cu01@test.com|127.0.0.1');
    }

    /* Paso 5-6: credenciales válidas → token y registro LOGIN en auditoría (CU29/RN07) */
    public function test_basic_flow_issues_token_and_audits_login(): void
    {
        $user = $this->user();
        $this->login()->assertStatus(200)->assertJsonStructure(['token', 'user', 'expires_at']);
        $this->assertDatabaseHas('audits', ['action' => 'LOGIN', 'table_name' => 'users', 'record_id' => $user->id, 'user_id' => $user->id]);
    }

    /* E1: campos vacíos o correo mal formado → 422 por campo */
    public function test_e1_empty_or_malformed_fields(): void
    {
        $this->postJson('/api/login', ['email' => 'no-es-correo', 'password' => ''])
            ->assertStatus(422)->assertJsonValidationErrors(['email', 'password']);
    }

    /* E2: credenciales incorrectas → mensaje genérico, sin auditoría de login */
    public function test_e2_wrong_credentials_generic_message(): void
    {
        $this->user();
        $this->login('otra')->assertStatus(401)->assertJson(['message' => 'Credenciales incorrectas']);
        $this->postJson('/api/login', ['email' => 'noexiste@test.com', 'password' => 'x'])
            ->assertStatus(401)->assertJson(['message' => 'Credenciales incorrectas']);
        $this->assertDatabaseMissing('audits', ['action' => 'LOGIN']);
    }

    /* E3: usuario inactivo → mensaje específico */
    public function test_e3_inactive_user_message(): void
    {
        $this->user(['status' => 'INACTIVO']);
        $this->login()->assertStatus(403)
            ->assertJson(['message' => 'Su usuario está inactivo. Contacte al administrador del sistema.']);
    }

    /* E4 / RN14: 5 fallos en un minuto → 429 con tiempo de espera, aun con la clave correcta */
    public function test_e4_five_failures_lock_for_sixty_seconds(): void
    {
        $this->user();
        for ($i = 0; $i < 5; $i++) {
            $this->login('mala')->assertStatus(401);
        }
        $r = $this->login()->assertStatus(429);
        $this->assertGreaterThan(0, $r->json('retry_after'));
        $this->assertLessThanOrEqual(60, $r->json('retry_after'));
    }

    /* Un login exitoso reinicia el contador de fallos */
    public function test_successful_login_resets_failure_counter(): void
    {
        $this->user();
        for ($i = 0; $i < 4; $i++) {
            $this->login('mala');
        }
        $this->login()->assertStatus(200);
        for ($i = 0; $i < 4; $i++) {
            $this->login('mala')->assertStatus(401);
        }
    }

    /* RNF03: sin «Recordarme» el token vence a las 8 h */
    public function test_rnf03_token_expires_in_eight_hours(): void
    {
        $user = $this->user();
        $this->login()->assertStatus(200);
        $exp = $user->tokens()->latest('id')->first()->expires_at;
        $this->assertNotNull($exp);
        $this->assertEqualsWithDelta(now()->addHours(8)->timestamp, $exp->timestamp, 60);
    }

    /* A2: «Recordarme» → la sesión dura 30 días */
    public function test_a2_remember_me_thirty_days(): void
    {
        $user = $this->user();
        $this->login('Clave#2026', ['remember' => true])->assertStatus(200);
        $exp = $user->tokens()->latest('id')->first()->expires_at;
        $this->assertEqualsWithDelta(now()->addDays(30)->timestamp, $exp->timestamp, 60);
    }

    /* Un token vencido ya no autentica */
    public function test_expired_token_is_rejected(): void
    {
        $user  = $this->user();
        $token = $user->createToken('t', ['*'], now()->subMinute())->plainTextToken;
        $this->withToken($token)->getJson('/api/me')->assertStatus(401);
    }
}
