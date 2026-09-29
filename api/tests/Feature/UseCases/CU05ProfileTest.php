<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * CU05 – Consultar y actualizar perfil (RF01, RN10, RNF03, RNF12, CU29 include).
 */
class CU05ProfileTest extends TestCase
{
    use RefreshDatabase;

    private function login(User $u): string
    {
        return $u->createToken('t', ['*'], now()->addHours(8))->plainTextToken;
    }

    /* Paso 2: el perfil muestra nombre, correo, rol, teléfono y fecha de registro */
    public function test_profile_shows_basic_data_including_phone_and_registration_date(): void
    {
        $u = User::factory()->create(['phone' => '3001234567']);
        Sanctum::actingAs($u);
        $this->getJson('/api/me')->assertOk()
            ->assertJsonPath('user.phone', '3001234567')
            ->assertJsonStructure(['user' => ['name', 'email', 'role', 'phone', 'created_at']]);
    }

    /* Paso 3-5: editar el teléfono, auditado */
    public function test_update_phone_is_saved_encrypted_and_audited(): void
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);
        $this->putJson('/api/profile', ['name' => $u->name, 'email' => $u->email, 'phone' => '3009998877'])
            ->assertOk()->assertJsonPath('user.phone', '3009998877');

        $raw = \Illuminate\Support\Facades\DB::table('users')->where('id', $u->id)->value('phone');
        $this->assertStringNotContainsString('3009998877', $raw);   /* RNF12: cifrado en reposo */
        $this->assertSame('3009998877', $u->fresh()->phone);
        $this->assertDatabaseHas('audits', ['table_name' => 'users', 'record_id' => $u->id, 'action' => 'UPDATE']);
    }

    /* E1: teléfono inválido */
    public function test_e1_invalid_phone(): void
    {
        $u = User::factory()->create();
        Sanctum::actingAs($u);
        foreach (['123', 'abc1234567', str_repeat('9', 16)] as $bad) {
            $this->putJson('/api/profile', ['name' => $u->name, 'email' => $u->email, 'phone' => $bad])
                ->assertStatus(422)->assertJsonValidationErrors(['phone']);
        }
    }

    /* A1: cambiar contraseña exige la actual + RN10, guarda el hash */
    public function test_a1_change_password_requires_current_password_and_rn10(): void
    {
        $u = User::factory()->create(['password' => Hash::make('Vieja#2026')]);
        Sanctum::actingAs($u);
        $this->putJson('/api/profile', [
            'name' => $u->name, 'email' => $u->email,
            'current_password' => 'Vieja#2026', 'password' => 'Nueva#2026Xx', 'password_confirmation' => 'Nueva#2026Xx',
        ])->assertOk();
        $this->assertTrue(Hash::check('Nueva#2026Xx', $u->fresh()->password));
    }

    /* E2 */
    public function test_e2_wrong_current_password(): void
    {
        $u = User::factory()->create(['password' => Hash::make('Vieja#2026')]);
        Sanctum::actingAs($u);
        $this->putJson('/api/profile', [
            'name' => $u->name, 'email' => $u->email,
            'current_password' => 'Incorrecta1!', 'password' => 'Nueva#2026Xx', 'password_confirmation' => 'Nueva#2026Xx',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password' => 'La contraseña actual no es correcta.']);
        $this->assertTrue(Hash::check('Vieja#2026', $u->fresh()->password));
    }

    public function test_current_password_is_required_to_change_password(): void
    {
        $u = User::factory()->create(['password' => Hash::make('Vieja#2026')]);
        Sanctum::actingAs($u);
        $this->putJson('/api/profile', [
            'name' => $u->name, 'email' => $u->email,
            'password' => 'Nueva#2026Xx', 'password_confirmation' => 'Nueva#2026Xx',
        ])->assertStatus(422)->assertJsonValidationErrors(['current_password']);
    }

    /* A1: cierra las demás sesiones, conserva la actual */
    public function test_a1_closes_other_sessions_but_keeps_the_current_one(): void
    {
        $u = User::factory()->create(['password' => Hash::make('Vieja#2026')]);
        $tokenA = $this->login($u);
        $tokenB = $this->login($u);

        $this->withToken($tokenA)->putJson('/api/profile', [
            'name' => $u->name, 'email' => $u->email,
            'current_password' => 'Vieja#2026', 'password' => 'Nueva#2026Xx', 'password_confirmation' => 'Nueva#2026Xx',
        ])->assertOk();

        $this->assertSame(1, $u->tokens()->count());
        $this->app['auth']->forgetGuards();
        $this->withToken($tokenA)->getJson('/api/me')->assertOk();
        $this->app['auth']->forgetGuards();
        $this->withToken($tokenB)->getJson('/api/me')->assertStatus(401);
    }

    /* Sin cambiar contraseña, no se tocan las demás sesiones */
    public function test_updating_name_only_does_not_revoke_other_sessions(): void
    {
        $u = User::factory()->create();
        $tokenA = $this->login($u); $tokenB = $this->login($u);
        $this->withToken($tokenA)->putJson('/api/profile', ['name' => 'Nuevo Nombre', 'email' => $u->email])->assertOk();
        $this->assertSame(2, $u->tokens()->count());
    }
}
