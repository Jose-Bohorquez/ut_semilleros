<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Notification;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU04 – Recuperar contraseña (RF01, RN10, RNF03, CU29 include).
 */
class CU04ForgotPasswordTest extends TestCase
{
    use RefreshDatabase;

    private const EMAIL = 'cu04@test.com';
    private const NEW_PW = 'Nueva#Clave2026';

    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('forgot-password:' . self::EMAIL);
    }

    private function user(): User
    {
        return User::factory()->create(['email' => self::EMAIL, 'password' => Hash::make('Vieja#2026'), 'email_verified_at' => now()]);
    }

    /* Paso 5: el mensaje NUNCA revela si el correo existe (antes usaba el
       texto de Laravel, que sí lo revelaba). */
    public function test_message_never_reveals_whether_the_email_is_registered(): void
    {
        $this->user();
        $r1 = $this->postJson('/api/forgot-password', ['email' => self::EMAIL])->assertOk()->json('message');
        RateLimiter::clear('forgot-password:noexiste@test.com');
        $r2 = $this->postJson('/api/forgot-password', ['email' => 'noexiste@test.com'])->assertOk()->json('message');
        $this->assertSame($r1, $r2);
        $this->assertStringContainsString('Si el correo está registrado', $r1);
    }

    public function test_registered_user_gets_a_real_token(): void
    {
        $u = $this->user();
        $this->postJson('/api/forgot-password', ['email' => self::EMAIL])->assertOk();
        $this->assertDatabaseHas('password_reset_tokens', ['email' => self::EMAIL]);
    }

    /* A1: reenvío invalida el token anterior (comportamiento nativo de Laravel) */
    public function test_resending_invalidates_the_previous_token(): void
    {
        $this->user();
        $this->postJson('/api/forgot-password', ['email' => self::EMAIL]);
        $first = \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', self::EMAIL)->value('token');
        $this->travel(1)->minutes();
        $this->postJson('/api/forgot-password', ['email' => self::EMAIL]);
        $second = \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', self::EMAIL)->value('token');
        $this->assertNotSame($first, $second);
        $this->assertSame(1, \Illuminate\Support\Facades\DB::table('password_reset_tokens')->where('email', self::EMAIL)->count());
    }

    /* E3: más de 3 solicitudes en 10 minutos para el mismo correo → 429 */
    public function test_e3_more_than_3_requests_in_10_minutes_are_ignored(): void
    {
        $this->user();
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/forgot-password', ['email' => self::EMAIL])->assertOk();
        }
        $this->postJson('/api/forgot-password', ['email' => self::EMAIL])
            ->assertStatus(429)->assertJsonStructure(['message', 'retry_after']);
    }

    /* No cuenta por IP: otro correo desde la misma IP no se ve afectado */
    public function test_e3_limit_is_per_email_not_per_ip(): void
    {
        User::factory()->create(['email' => 'otro@test.com']);
        for ($i = 0; $i < 3; $i++) {
            $this->postJson('/api/forgot-password', ['email' => self::EMAIL]);
        }
        $this->postJson('/api/forgot-password', ['email' => 'otro@test.com'])->assertOk();
    }

    private function reset(string $token, array $o = [])
    {
        return $this->postJson('/api/reset-password', array_merge([
            'token' => $token, 'email' => self::EMAIL,
            'password' => self::NEW_PW, 'password_confirmation' => self::NEW_PW,
        ], $o));
    }

    /* Paso 9-10: política de contraseña (RN10), hash guardado, token invalidado */
    public function test_basic_flow_updates_password_and_audits(): void
    {
        $u = $this->user();
        $token = Password::createToken($u);
        $this->reset($token)->assertOk();
        $this->assertTrue(Hash::check(self::NEW_PW, $u->fresh()->password));
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => self::EMAIL]);
        $this->assertDatabaseHas('audits', ['action' => 'PASSWORD_RESET', 'user_id' => $u->id, 'record_id' => $u->id]);
    }

    /* Paso 10: TODAS las sesiones abiertas quedan invalidadas, no solo la del dispositivo actual */
    public function test_all_previous_sessions_are_revoked(): void
    {
        $u = $this->user();
        $u->createToken('a', ['*'], now()->addHour());
        $u->createToken('b', ['*'], now()->addHour());
        $this->assertSame(2, $u->tokens()->count());
        $this->reset(Password::createToken($u))->assertOk();
        $this->assertSame(0, $u->fresh()->tokens()->count());
    }

    /* RN10: la nueva contraseña debe cumplir la política */
    public function test_rn10_password_policy_is_enforced(): void
    {
        $u = $this->user();
        foreach (['corta1!', 'todaminusculas1!', 'TODOMAYUSCULAS1!', 'SinNumeroSimbolo', 'SinSimbolo123'] as $weak) {
            $this->reset(Password::createToken($u), ['password' => $weak, 'password_confirmation' => $weak])
                ->assertStatus(422)->assertJsonValidationErrors(['password']);
        }
    }

    public function test_password_confirmation_must_match(): void
    {
        $u = $this->user();
        $this->reset(Password::createToken($u), ['password_confirmation' => 'Otra#Clave2026'])
            ->assertStatus(422)->assertJsonValidationErrors(['password' => 'Las contraseñas no coinciden.']);
    }

    /* E1: token vencido o ya usado */
    public function test_e1_invalid_or_expired_token(): void
    {
        $u = $this->user();
        $this->reset('token-que-no-existe')
            ->assertStatus(422)->assertJsonValidationErrors(['email' => 'El enlace no es válido o expiró.']);

        $token = Password::createToken($u);
        $this->reset($token)->assertOk();
        $this->reset($token)   /* ya usado */
            ->assertStatus(422)->assertJsonValidationErrors(['email' => 'El enlace no es válido o expiró.']);
    }

    public function test_e4_notifications_are_queued_for_retry(): void
    {
        Notification::fake();
        $u = $this->user();
        $this->postJson('/api/forgot-password', ['email' => self::EMAIL])->assertOk();
        Notification::assertSentTo($u, CustomResetPasswordNotification::class, function ($n) {
            return $n instanceof \Illuminate\Contracts\Queue\ShouldQueue;
        });
    }

    /* E4: si el envío falla (SMTP caído, cola en modo síncrono), nunca se
       filtra como 500 — la respuesta sigue siendo el mensaje genérico. */
    public function test_e4_mail_failure_never_surfaces_as_a_server_error(): void
    {
        $this->user();
        Notification::shouldReceive('send')->andThrow(new \RuntimeException('SMTP caído'));
        $this->postJson('/api/forgot-password', ['email' => self::EMAIL])
            ->assertOk()->assertJsonFragment(['message' => 'Si el correo está registrado, recibirá un enlace para recuperar su contraseña.']);
    }
}
