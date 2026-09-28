<?php

namespace Tests\Feature\UseCases;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * RF16 / RN09 / CU02 A1 – Autorización de tratamiento de datos personales (Ley 1581).
 */
class RF16ConsentTest extends TestCase
{
    use RefreshDatabase;

    private function student(bool $consented = false): User
    {
        return User::factory()->create(['role' => 'ESTUDIANTE', 'data_consent_at' => $consented ? now() : null]);
    }

    /* Criterio: ningún estudiante usa la aplicación sin autorización registrada */
    public function test_student_without_consent_is_blocked_by_the_api(): void
    {
        Sanctum::actingAs($this->student());
        $this->getJson('/api/seedbeds')->assertStatus(403)->assertJson(['code' => 'CONSENT_REQUIRED']);
        $this->getJson('/api/me')->assertOk();   /* lo mínimo para mostrar el aviso */
    }

    public function test_accept_stores_date_audits_and_unblocks(): void
    {
        $u = $this->student();
        Sanctum::actingAs($u);
        $this->postJson('/api/consent', ['accept' => true])->assertOk()->assertJsonPath('user.data_consent_at', fn ($v) => !empty($v));
        $this->assertNotNull($u->fresh()->data_consent_at);
        $this->assertDatabaseHas('audits', ['action' => 'CONSENT', 'record_id' => $u->id]);
        $this->getJson('/api/seedbeds')->assertOk();
    }

    public function test_accept_twice_keeps_the_first_date(): void
    {
        $u = $this->student(true);
        $first = $u->fresh()->data_consent_at;
        $this->travel(2)->days();
        Sanctum::actingAs($u);
        $this->postJson('/api/consent', ['accept' => true])->assertOk();
        $this->assertEquals($first, $u->fresh()->data_consent_at);
    }

    /* A1.4: «No acepto» cierra la sesión (se revoca el token) */
    public function test_reject_revokes_the_token(): void
    {
        $u = $this->student();
        $token = $u->createToken('t', ['*'], now()->addHour())->plainTextToken;
        $this->withToken($token)->postJson('/api/consent', ['accept' => false])
            ->assertOk()->assertJson(['logged_out' => true]);
        $this->assertSame(0, $u->tokens()->count());
        $this->assertNull($u->fresh()->data_consent_at);
    }

    /* El aviso aplica a estudiantes (actor de RF16); los demás roles no se bloquean */
    public function test_other_roles_are_not_blocked(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'LIDER_SEMILLERO']));
        $this->getJson('/api/seedbeds')->assertOk();
    }

    /* data_consent_at no se puede fijar desde el perfil ni desde un formulario */
    public function test_consent_date_is_not_mass_assignable(): void
    {
        $u = $this->student(true);
        Sanctum::actingAs($u);
        $this->putJson('/api/profile', ['name' => 'X', 'email' => $u->email, 'data_consent_at' => '2000-01-01'])->assertOk();
        $this->assertNotEquals('2000-01-01', $u->fresh()->data_consent_at?->toDateString());
    }
}
