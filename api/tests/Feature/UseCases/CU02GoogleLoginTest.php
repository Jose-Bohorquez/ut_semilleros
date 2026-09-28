<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU02 – Iniciar sesión con cuenta institucional (RF01, RF16, RN04, RN09, RNF03).
 * Google se simula con Http::fake: nunca se llama al servicio real.
 */
class CU02GoogleLoginTest extends TestCase
{
    use RefreshDatabase;

    private const CLIENT_ID = 'test-client.apps.googleusercontent.com';

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.google.client_id' => self::CLIENT_ID, 'services.google.allowed_domains' => 'ut.edu.co']);
    }

    private function fakeGoogle(array $claims = [], int $status = 200): void
    {
        Http::fake(['oauth2.googleapis.com/*' => Http::response(array_merge([
            'iss' => 'https://accounts.google.com', 'aud' => self::CLIENT_ID, 'sub' => '1001',
            'email' => 'estudiante@ut.edu.co', 'email_verified' => 'true', 'hd' => 'ut.edu.co',
            'name' => 'Estudiante Prueba', 'exp' => (string) (time() + 3600),
        ], $claims), $status)]);
    }

    private function google()
    {
        return $this->postJson('/api/auth/google', ['credential' => 'id-token-de-prueba']);
    }

    /* Pasos 6-8: estudiante nuevo → se crea con rol ESTUDIANTE, token de 8 h, auditoría LOGIN */
    public function test_basic_flow_creates_student_and_issues_8h_token(): void
    {
        $this->fakeGoogle();
        $res = $this->google()->assertOk()->assertJsonStructure(['token', 'user', 'expires_at']);

        $user = User::where('email', 'estudiante@ut.edu.co')->firstOrFail();
        $this->assertSame('ESTUDIANTE', $user->role);
        $this->assertSame('Estudiante Prueba', $user->name);
        $this->assertSame('1001', $user->google_id);
        $this->assertNotNull($user->email_verified_at);
        $this->assertNull($res->json('user.data_consent_at'));   /* → A1 */
        $this->assertEqualsWithDelta(now()->addHours(8)->timestamp, strtotime($res->json('expires_at')), 60);
        $this->assertDatabaseHas('audits', ['action' => 'LOGIN', 'user_id' => $user->id]);

        Http::assertSent(fn ($r) => str_starts_with($r->url(), 'https://oauth2.googleapis.com/tokeninfo'));
    }

    /* Paso 7: si ya existe (p. ej. creado por el admin) se reutiliza y se vincula */
    public function test_existing_user_is_reused_and_linked(): void
    {
        $existing = User::factory()->create(['email' => 'estudiante@ut.edu.co', 'role' => 'LIDER_SEMILLERO']);
        $this->fakeGoogle(['email' => 'Estudiante@UT.edu.co']);
        $this->google()->assertOk();
        $this->assertSame(1, User::count());
        $this->assertSame('1001', $existing->fresh()->google_id);
        $this->assertSame('LIDER_SEMILLERO', $existing->fresh()->role);   /* no se degrada el rol */
    }

    /* E1: correo fuera del dominio → mensaje y no se crea usuario */
    public function test_e1_non_institutional_email_is_rejected(): void
    {
        $this->fakeGoogle(['email' => 'alguien@gmail.com', 'hd' => null]);
        $this->google()->assertStatus(403)->assertJson(['message' => 'Debe ingresar con su cuenta institucional']);
        $this->assertSame(0, User::count());
    }

    /* E1 (RN04): cuenta personal de Google con dirección @ut.edu.co (sin hd del Workspace) */
    public function test_e1_institutional_address_without_workspace_hd_is_rejected(): void
    {
        $this->fakeGoogle(['hd' => null]);
        $this->google()->assertStatus(403);
        $this->assertSame(0, User::count());
    }

    /* Excepción aprobada por Jose: un ADMIN_SISTEMA registrado puede usar otra cuenta de Google */
    public function test_existing_admin_may_use_external_google_account(): void
    {
        User::factory()->create(['email' => 'admin@gmail.com', 'role' => 'ADMIN_SISTEMA']);
        $this->fakeGoogle(['email' => 'admin@gmail.com', 'hd' => null]);
        $this->google()->assertOk();
    }

    public function test_external_account_of_non_admin_is_rejected(): void
    {
        User::factory()->create(['email' => 'lider@gmail.com', 'role' => 'LIDER_SEMILLERO']);
        $this->fakeGoogle(['email' => 'lider@gmail.com', 'hd' => null]);
        $this->google()->assertStatus(403)->assertJson(['message' => 'Debe ingresar con su cuenta institucional']);
    }

    /* E3: usuario inactivado */
    public function test_e3_inactive_user(): void
    {
        User::factory()->create(['email' => 'estudiante@ut.edu.co', 'status' => 'INACTIVO']);
        $this->fakeGoogle();
        $this->google()->assertStatus(403)->assertJson(['message' => 'Su acceso está inactivo']);
        $this->assertDatabaseMissing('audits', ['action' => 'LOGIN']);
    }

    /* E5: Google no responde, token inválido o emitido para otra app */
    public function test_e5_google_unavailable(): void
    {
        Http::fake(fn () => throw new ConnectionException('timeout'));
        $this->google()->assertStatus(503)
            ->assertJson(['message' => 'No fue posible autenticarse con Google, intente nuevamente']);
    }

    public function test_e5_invalid_token(): void
    {
        $this->fakeGoogle(['error' => 'invalid_token'], 400);
        $this->google()->assertStatus(401);
    }

    public function test_e5_token_for_another_client_or_expired_or_unverified(): void
    {
        foreach ([['aud' => 'otra-app'], ['exp' => (string) (time() - 10)], ['email_verified' => 'false'], ['iss' => 'evil.example']] as $bad) {
            $this->fakeGoogle($bad);
            $this->google()->assertStatus(401);
        }
        $this->assertSame(0, User::count());
    }

    public function test_e5_not_configured(): void
    {
        config(['services.google.client_id' => null]);
        $this->google()->assertStatus(503);
    }

    /* Un correo ya vinculado a otra cuenta de Google no se re-vincula */
    public function test_different_google_account_for_linked_user_is_rejected(): void
    {
        $u = User::factory()->create(['email' => 'estudiante@ut.edu.co']);
        $u->forceFill(['google_id' => '999'])->save();
        $this->fakeGoogle();
        $this->google()->assertStatus(403);
        $this->assertSame('999', $u->fresh()->google_id);
    }

    public function test_auth_config_exposes_client_id_only(): void
    {
        $this->getJson('/api/auth/config')->assertOk()
            ->assertExactJson(['google_client_id' => self::CLIENT_ID, 'institutional_domains' => ['ut.edu.co']]);
    }

    /* google_id no se expone en la API */
    public function test_google_id_is_not_exposed(): void
    {
        $this->fakeGoogle();
        $this->assertArrayNotHasKey('google_id', $this->google()->json('user'));
    }
}
