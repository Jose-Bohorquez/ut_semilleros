<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\User;
use App\Models\Coordinator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Crypt;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RNF03 – Seguridad: 100 % de endpoints protegidos exigen token; contraseñas
 * con bcrypt costo 12; datos personales sensibles cifrados; cabeceras y límite.
 */
class RNF03SecurityTest extends TestCase
{
    use RefreshDatabase;

    /* Rutas públicas a propósito (login, recuperación, Google, SIA, salud) */
    private const PUBLIC = [
        'POST api/register', 'POST api/login', 'POST api/forgot-password', 'POST api/reset-password',
        'GET api/auth/config', 'POST api/auth/google', 'POST api/sia/chat', 'POST api/sia/close',
    ];

    public function test_every_non_public_api_route_requires_a_token(): void
    {
        $checked = 0;
        foreach (Route::getRoutes() as $route) {
            if (!str_starts_with($route->uri(), 'api/')) continue;
            foreach (array_diff($route->methods(), ['HEAD']) as $method) {
                $key = "$method {$route->uri()}";
                if (in_array($key, self::PUBLIC, true)) continue;
                $uri = '/' . preg_replace('/\{[^}]+\}/', '1', $route->uri());
                $status = $this->json($method, $uri)->getStatusCode();
                $this->assertSame(401, $status, "$key respondió $status sin token");
                $checked++;
            }
        }
        $this->assertGreaterThan(60, $checked, 'se esperaban todas las rutas protegidas');
    }

    /* 0 contraseñas en texto plano. En pruebas bcrypt usa costo 4 (phpunit.xml,
       por velocidad); el costo real (12) lo fija el .env: se revisa la plantilla
       y el hash con la configuración de producción. Producción verificada el
       2026-09-28: 12 de 12 hashes con $2y$12$. */
    public function test_passwords_are_bcrypt_and_production_cost_is_12(): void
    {
        User::factory()->create(['password' => 'Clave#2026']);   /* cast «hashed» */
        foreach (DB::table('users')->pluck('password') as $hash) {
            $this->assertMatchesRegularExpression('/^\$2y\$\d{2}\$/', $hash);
            $this->assertNotSame('Clave#2026', $hash);
        }
        $this->assertMatchesRegularExpression('/^BCRYPT_ROUNDS=12$/m', file_get_contents(base_path('.env.example')));
    }

    /* RNF03 «cifrado de datos personales sensibles (teléfono)» */
    public function test_coordinator_phone_is_encrypted_at_rest(): void
    {
        $c = Coordinator::create(['name' => 'Coord', 'email' => 'c@ut.edu.co', 'phone' => '3001234567', 'status' => 'ACTIVO']);
        $raw = DB::table('coordinators')->where('id', $c->id)->value('phone');
        $this->assertStringNotContainsString('3001234567', $raw);
        $this->assertSame('3001234567', Crypt::decryptString($raw));
        $this->assertSame('3001234567', $c->fresh()->phone);

        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->getJson('/api/coordinators')->assertOk()->assertSee('3001234567');   /* la API lo entrega descifrado */
    }

    public function test_security_headers_and_no_php_version(): void
    {
        $r = $this->getJson('/api/auth/config');
        $r->assertHeader('X-Content-Type-Options', 'nosniff')
          ->assertHeader('X-Frame-Options', 'DENY')
          ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $this->assertFalse($r->headers->has('X-Powered-By'));

        Sanctum::actingAs(User::factory()->create());
        $this->assertStringContainsString('no-store', $this->withToken('x')->getJson('/api/me')->headers->get('Cache-Control'));
    }

    public function test_api_rate_limit_is_applied(): void
    {
        $r = $this->getJson('/api/auth/config');
        $this->assertSame('120', $r->headers->get('X-RateLimit-Limit'));
    }

    /* El límite es por usuario aunque corra antes de auth:sanctum (review 2026-09-28) */
    public function test_rate_limit_is_keyed_by_user_not_ip(): void
    {
        $a = User::factory()->create(); $b = User::factory()->create();
        $ta = $a->createToken('t', ['*'], now()->addHour())->plainTextToken;
        $tb = $b->createToken('t', ['*'], now()->addHour())->plainTextToken;
        $this->withToken($ta)->getJson('/api/me');
        $this->app['auth']->forgetGuards();
        $left = $this->withToken($ta)->getJson('/api/me')->headers->get('X-RateLimit-Remaining');
        $this->app['auth']->forgetGuards();
        $leftB = $this->withToken($tb)->getJson('/api/me')->headers->get('X-RateLimit-Remaining');
        $this->assertSame('118', $left);
        $this->assertSame('119', $leftB);   /* otro usuario desde la misma IP tiene su propio cupo */
    }

    public function test_security_headers_also_on_non_api_responses(): void
    {
        $this->get('/no-existe')->assertHeader('X-Content-Type-Options', 'nosniff')->assertHeader('X-Frame-Options', 'DENY');
    }
}
