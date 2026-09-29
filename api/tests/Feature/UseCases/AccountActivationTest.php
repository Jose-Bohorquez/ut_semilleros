<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF01 — correo de registro: el administrador crea la cuenta y el usuario la
 * activa definiendo su contraseña. El enlace dura 7 días (broker «activations»).
 */
class AccountActivationTest extends TestCase
{
    use RefreshDatabase;

    private function pending(array $a = []): User
    {
        return User::factory()->create(array_merge(['email' => 'nuevo@ut.edu.co', 'email_verified_at' => null], $a));
    }

    private function reset(string $token, bool $activation = true)
    {
        return $this->postJson('/api/reset-password', [
            'token' => $token, 'email' => 'nuevo@ut.edu.co',
            'password' => 'Nueva#2026', 'password_confirmation' => 'Nueva#2026', 'activation' => $activation,
        ]);
    }

    public function test_admin_creating_user_without_password_sends_activation_mail(): void
    {
        Notification::fake();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->postJson('/api/users', ['name' => 'Pablo E Cuenca', 'email' => 'nuevo@ut.edu.co', 'role' => 'LIDER_SEMILLERO', 'status' => 'ACTIVO', 'authorization_reference' => 'Oficio 001 de 2026'])->assertSuccessful();
        Notification::assertSentTo(User::where('email', 'nuevo@ut.edu.co')->first(), AccountActivationNotification::class);
    }

    public function test_activation_link_still_works_after_3_days_and_verifies_email(): void
    {
        $u = $this->pending();
        $token = Password::broker('activations')->createToken($u);
        $this->travel(3)->days();
        $this->reset($token)->assertOk();
        $u->refresh();
        $this->assertTrue(Hash::check('Nueva#2026', $u->password));
        $this->assertNotNull($u->email_verified_at);
    }

    public function test_activation_link_expires_after_7_days(): void
    {
        $u = $this->pending();
        $token = Password::broker('activations')->createToken($u);
        $this->travel(8)->days();
        $this->reset($token)->assertStatus(422);
    }

    /* Un enlace de «olvidé mi contraseña» no gana 7 días pasando activation=1 si la cuenta ya está activa */
    public function test_forgot_password_token_keeps_60_minutes_for_active_accounts(): void
    {
        $u = $this->pending(['email_verified_at' => now()]);
        $token = Password::createToken($u);
        $this->travel(2)->hours();
        $this->reset($token, true)->assertStatus(422);
    }

    public function test_template_renders_role_and_link(): void
    {
        $u = $this->pending(['name' => 'PABLO E Cuenca', 'role' => 'LIDER_SEMILLERO']);
        $html = view('emails.activation', AccountActivationNotification::viewData($u, 'https://ut-edu.online/reset-password?token=abc&activation=1'))->render();
        $this->assertStringContainsString('Hola, Pablo', $html);
        $this->assertStringContainsString('Líder de semillero', $html);
        $this->assertStringContainsString('token=abc&amp;activation=1', $html);
        $this->assertStringContainsString('7 días', $html);
        $this->assertStringNotContainsString('tratamiento de datos</strong> (Ley 1581', $html);   /* solo para estudiantes */
    }

    /* Marca «Semilleros UT» (no APP_NAME) y WhatsApp al pie en ambos correos */
    public function test_mails_use_semilleros_ut_brand_and_whatsapp(): void
    {
        $u = $this->pending(['name' => 'Duban Rodríguez']);
        $act = (new AccountActivationNotification('tok'))->toMail($u);
        $this->assertSame('Activa tu cuenta · Semilleros UT', $act->subject);
        $this->assertSame('Semilleros UT', $act->from[1]);
        $html = (string) $act->render();
        $this->assertStringContainsString('wa.me/573178773186', $html);
        $this->assertStringNotContainsString('PWA UT Semilleros', $html);

        $reset = (new \App\Notifications\CustomResetPasswordNotification('tok'))->toMail($u);
        $this->assertSame('Recupera tu contraseña · Semilleros UT', $reset->subject);
        $this->assertStringContainsString('wa.me/573178773186', (string) $reset->render());
    }
}
