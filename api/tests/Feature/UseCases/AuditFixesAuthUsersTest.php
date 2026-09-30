<?php

namespace Tests\Feature\UseCases;

use App\Models\User;
use App\Notifications\CustomResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Regresiones de la auditoría (Auth / Perfil / Usuarios): CU05-H1/H2/H3,
 * CU04-H1, CU01-H1, T1 (traducciones), CU06-H2/H3 y GET /users.
 */
class AuditFixesAuthUsersTest extends TestCase
{
    use RefreshDatabase;

    private const PNG = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==';

    private function admin(array $a = []): User
    {
        return User::factory()->create($a + ['role' => 'ADMIN_SISTEMA', 'authorization_reference' => 'Oficio 1']);
    }

    /* ── CU05-H1 ─────────────────────────────────────────── */

    public function test_cu05_h1_profile_update_does_not_change_email_or_name(): void
    {
        $u = User::factory()->create(['role' => 'ESTUDIANTE', 'email' => 'yo@ut.edu.co', 'name' => 'Nombre Original']);
        Sanctum::actingAs($u);

        $this->putJson('/api/profile', ['name' => 'Otro Nombre', 'email' => 'otro@ut.edu.co', 'phone' => '3001112233'])
            ->assertOk()
            ->assertJsonPath('user.email', 'yo@ut.edu.co')
            ->assertJsonPath('user.phone', '3001112233');

        $u->refresh();
        $this->assertSame('yo@ut.edu.co', $u->email);
        $this->assertSame('Nombre Original', $u->name);
    }

    public function test_cu05_h1_changing_password_without_sending_phone_keeps_the_phone(): void
    {
        $u = User::factory()->create(['phone' => '3001112233', 'password' => Hash::make('Vieja#2026')]);
        Sanctum::actingAs($u);

        $this->putJson('/api/profile', [
            'current_password' => 'Vieja#2026', 'password' => 'Nueva#2026Xx', 'password_confirmation' => 'Nueva#2026Xx',
        ])->assertOk();

        $this->assertSame('3001112233', $u->fresh()->phone);
    }

    /* ── CU05-H3: límite de intentos de current_password ── */

    public function test_cu05_h3_current_password_attempts_are_rate_limited(): void
    {
        $u = User::factory()->create(['password' => Hash::make('Vieja#2026')]);
        RateLimiter::clear('profile-password:' . $u->id);
        Sanctum::actingAs($u);
        $body = ['current_password' => 'Incorrecta1!', 'password' => 'Nueva#2026Xx', 'password_confirmation' => 'Nueva#2026Xx'];

        for ($i = 0; $i < 5; $i++) {
            $this->putJson('/api/profile', $body)->assertStatus(422);
        }
        $this->putJson('/api/profile', $body)->assertStatus(429)->assertJsonStructure(['message', 'retry_after']);

        /* aun con la contraseña correcta, mientras dure el bloqueo */
        $this->putJson('/api/profile', ['current_password' => 'Vieja#2026'] + $body)->assertStatus(429);
        $this->assertTrue(Hash::check('Vieja#2026', $u->fresh()->password));
    }

    /* ── CU05-H2: foto de perfil ─────────────────────────── */

    public function test_cu05_h2_valid_png_is_stored(): void
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);

        $this->postJson('/api/profile/photo', ['photo' => 'data:image/png;base64,' . self::PNG])->assertOk();
        $this->assertStringStartsWith('data:image/png;base64,', $u->fresh()->profile_photo);
    }

    public function test_cu05_h2_svg_is_rejected(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $svg = base64_encode('<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>');

        $this->postJson('/api/profile/photo', ['photo' => 'data:image/svg+xml;base64,' . $svg])
            ->assertStatus(422)->assertJsonPath('message', 'Formato de imagen no válido. Use JPEG, PNG, GIF, WebP o BMP.');
    }

    public function test_cu05_h2_content_must_really_be_an_image(): void
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);

        /* prefijo de imagen pero bytes que no lo son */
        $this->postJson('/api/profile/photo', ['photo' => 'data:image/png;base64,' . base64_encode('<?php echo 1;')])
            ->assertStatus(422);
        /* base64 inválido */
        $this->postJson('/api/profile/photo', ['photo' => 'data:image/png;base64,@@@no-es-base64@@@'])
            ->assertStatus(422);
        $this->assertNull($u->fresh()->profile_photo);
    }

    public function test_cu05_h3_photo_over_2mb_is_rejected_with_the_real_limit_in_the_message(): void
    {
        Sanctum::actingAs(User::factory()->create());
        $big = base64_encode(str_repeat('A', 2 * 1024 * 1024 + 1));

        $this->postJson('/api/profile/photo', ['photo' => 'data:image/png;base64,' . $big])
            ->assertStatus(422)->assertJsonPath('message', 'La imagen es demasiado grande. Máximo 2 MB.');
    }

    public function test_photo_can_be_deleted(): void
    {
        $u = User::factory()->create(['profile_photo' => 'data:image/png;base64,' . self::PNG]);
        Sanctum::actingAs($u);

        $this->deleteJson('/api/profile/photo')->assertOk();
        $this->assertNull($u->fresh()->profile_photo);
    }

    /* ── CU04-H1: usuario inactivo ───────────────────────── */

    public function test_cu04_h1_inactive_user_gets_no_reset_link_but_same_generic_answer(): void
    {
        Notification::fake();
        $activo   = User::factory()->create(['email' => 'activo@ut.edu.co', 'status' => 'ACTIVO']);
        $inactivo = User::factory()->create(['email' => 'inactivo@ut.edu.co', 'status' => 'INACTIVO']);

        $m1 = $this->postJson('/api/forgot-password', ['email' => 'activo@ut.edu.co'])->assertOk()->json('message');
        $m2 = $this->postJson('/api/forgot-password', ['email' => 'inactivo@ut.edu.co'])->assertOk()->json('message');

        $this->assertSame($m1, $m2);
        Notification::assertSentTo($activo, CustomResetPasswordNotification::class);
        Notification::assertNotSentTo($inactivo, CustomResetPasswordNotification::class);
        $this->assertDatabaseMissing('password_reset_tokens', ['email' => 'inactivo@ut.edu.co']);
    }

    public function test_cu04_h1_inactive_user_cannot_reset_with_a_valid_token(): void
    {
        $u = User::factory()->create(['email' => 'inact2@ut.edu.co', 'password' => Hash::make('Vieja#2026'), 'status' => 'ACTIVO']);
        $token = Password::broker()->createToken($u);
        $u->update(['status' => 'INACTIVO']);   /* se inactivó después de pedir el enlace */

        $this->postJson('/api/reset-password', [
            'token' => $token, 'email' => 'inact2@ut.edu.co',
            'password' => 'Nueva#2026Xx', 'password_confirmation' => 'Nueva#2026Xx',
        ])->assertStatus(422)->assertJsonValidationErrors(['email']);

        $this->assertTrue(Hash::check('Vieja#2026', $u->fresh()->password));
    }

    /* ── CU01-H1: /register ya no existe ─────────────────── */

    public function test_cu01_h1_register_route_is_gone(): void
    {
        $status = $this->postJson('/api/register', ['name' => 'X', 'email' => 'x@ut.edu.co', 'password' => 'Abc#12345'])->getStatusCode();
        $this->assertContains($status, [404, 405]);
    }

    /* ── T1: traducciones ────────────────────────────────── */

    public function test_t1_validation_messages_are_in_spanish_not_raw_keys(): void
    {
        $this->assertSame('es', app()->getLocale());

        $r = $this->postJson('/api/forgot-password', [])->assertStatus(422);
        $msg = $r->json('errors.email.0');

        $this->assertStringNotContainsString('validation.', $msg);
        $this->assertSame('El correo es obligatorio.', $msg);
    }

    public function test_t1_unique_message_is_in_spanish(): void
    {
        $this->admin(['email' => 'dup@ut.edu.co']);
        Sanctum::actingAs($this->admin());

        $msg = $this->postJson('/api/users', [
            'name' => 'Dup', 'email' => 'dup@ut.edu.co', 'role' => 'ESTUDIANTE', 'status' => 'ACTIVO',
        ])->assertStatus(422)->json('errors.email.0');

        $this->assertStringNotContainsString('validation.', $msg);
        $this->assertSame('El correo ya está registrado.', $msg);
    }

    /* ── CU06-H2: no degradar al admin ───────────────────── */

    private function updatePayload(User $u, array $over = []): array
    {
        return $over + [
            'name' => $u->name, 'email' => $u->email, 'role' => $u->role,
            'status' => $u->status, 'authorization_reference' => 'Oficio 1',
        ];
    }

    public function test_cu06_h2_admin_cannot_change_their_own_role(): void
    {
        $a = $this->admin();
        $this->admin();   /* hay otro admin activo: aun así no a sí mismo */
        Sanctum::actingAs($a);

        $this->putJson("/api/users/{$a->id}", $this->updatePayload($a, ['role' => 'LIDER_SEMILLERO']))
            ->assertStatus(422)->assertJsonValidationErrors(['role']);

        $this->assertSame('ADMIN_SISTEMA', $a->fresh()->role);
    }

    public function test_cu06_h2_admin_can_demote_another_admin_when_one_active_remains(): void
    {
        $a = $this->admin();
        $b = $this->admin();
        Sanctum::actingAs($a);

        $this->putJson("/api/users/{$b->id}", $this->updatePayload($b, ['role' => 'LIDER_SEMILLERO']))->assertOk();
        $this->assertSame('LIDER_SEMILLERO', $b->fresh()->role);
    }

    public function test_cu06_h2_keeping_the_admin_role_still_works(): void
    {
        $a = $this->admin();
        Sanctum::actingAs($a);

        $this->putJson("/api/users/{$a->id}", $this->updatePayload($a, ['name' => 'Nuevo Nombre']))->assertOk();
    }

    /* ── CU06-H3: reenvío con SMTP caído ─────────────────── */

    public function test_cu06_h3_resend_activation_returns_controlled_error_when_mail_fails(): void
    {
        $target = User::factory()->create(['role' => 'ESTUDIANTE', 'email_verified_at' => null]);
        Sanctum::actingAs($this->admin());

        $this->mock(\Illuminate\Contracts\Notifications\Dispatcher::class, function ($m) {
            $m->shouldReceive('send')->andThrow(new \RuntimeException('SMTP caído'));
            $m->shouldReceive('sendNow')->andThrow(new \RuntimeException('SMTP caído'));
        });

        $this->postJson("/api/users/{$target->id}/resend-activation")
            ->assertStatus(503)
            ->assertJsonPath('message', 'No se pudo enviar el correo de activación. Intenta de nuevo más tarde.');
    }

    /* ── GET /users ──────────────────────────────────────── */

    public function test_get_users_full_data_only_for_admin(): void
    {
        $this->admin(['email' => 'root@ut.edu.co']);
        Sanctum::actingAs($this->admin());

        $u = $this->getJson('/api/users')->assertOk()->json('users.0');
        $this->assertArrayHasKey('email', $u);
        $this->assertArrayHasKey('authorization_reference', $u);
    }

    public function test_get_users_is_minimal_for_leader_and_administrative_and_denied_to_student(): void
    {
        $this->admin(['email' => 'root@ut.edu.co']);

        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO'] as $role) {
            Sanctum::actingAs(User::factory()->create(['role' => $role, 'authorization_reference' => 'Oficio 2']));
            $u = $this->getJson('/api/users')->assertOk()->json('users.0');
            $this->assertSame(['id', 'name', 'role', 'status'], array_keys($u));
        }

        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));
        $this->getJson('/api/users')->assertStatus(403);
    }
}
