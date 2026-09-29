<?php

namespace Tests\Feature\User;

use Tests\TestCase;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * ADMIN_SISTEMA puede acceder a /api/users
     */
    public function test_admin_can_access_users_endpoint(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA',

            'status' => 'ACTIVO',

        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'users'

            ]);
    }

    /**
     * Usuario no autorizado no puede acceder.
     */
    public function test_non_admin_cannot_access_users_endpoint(): void
    {
        $student = User::factory()->create([

            'role' => 'ESTUDIANTE',

            'status' => 'ACTIVO',

        ]);

        Sanctum::actingAs($student);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(403)
            ->assertJson([

                'message' => 'No autorizado para realizar esta acción'

            ]);
    }

    /**
     * Cambio de estado sin eliminación física.
     */
    public function test_user_status_can_be_toggled_without_deleting_user(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMIN_SISTEMA',

            'status' => 'ACTIVO',

        ]);

        $user = User::factory()->create([

            'status' => 'ACTIVO',

        ]);

        Sanctum::actingAs($admin);

        $response = $this->putJson(

            "/api/users/{$user->id}/toggle-status"
        );

        $response->assertStatus(200);

        $this->assertDatabaseHas('users', [

            'id' => $user->id

        ]);

        $this->assertDatabaseMissing('users', [

            'id' => $user->id,
            'status' => 'ACTIVO'

        ]);
    }

    /**
     * Lider semillero puede leer la lista de usuarios (solo lectura).
     */
    public function test_leader_can_read_users_list(): void
    {
        $leader = User::factory()->create([

            'role' => 'LIDER_SEMILLERO'

        ]);

        Sanctum::actingAs($leader);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'users'

            ]);
    }


    /**
     * Administrativo puede leer la lista de usuarios (solo lectura).
     */
    public function test_administrative_can_read_users_list(): void
    {
        $admin = User::factory()->create([

            'role' => 'ADMINISTRATIVO'

        ]);

        Sanctum::actingAs($admin);

        $response = $this->getJson('/api/users');

        $response
            ->assertStatus(200)
            ->assertJsonStructure([

                'users'

            ]);
    }

    /* ───── CU06-E3: no auto-inactivarse ni inactivar al último admin activo ───── */

    public function test_admin_cannot_inactivate_self_via_toggle(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$admin->id}/toggle-status")
            ->assertStatus(422)->assertJsonValidationErrors(['status']);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'status' => 'ACTIVO']);
    }

    /* El actor siempre es un ADMIN_SISTEMA activo (auth:sanctum + 'active' lo
       exigen), así que al inactivar a OTRO admin siempre queda al menos el
       actor activo — la rama "último admin" del guard es inalcanzable por
       este flujo con un solo actor; solo el auto-caso (arriba) es real. Este
       test confirma el camino positivo: inactivar a otro admin sí funciona
       mientras alguien más quede activo. */
    public function test_can_inactivate_admin_when_another_stays_active(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        $otherAdmin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        Sanctum::actingAs($admin);

        $this->putJson("/api/users/{$otherAdmin->id}/toggle-status")->assertOk();
        $this->assertDatabaseHas('users', ['id' => $otherAdmin->id, 'status' => 'INACTIVO']);
    }

    public function test_update_can_inactivate_other_admin_when_actor_stays_active(): void
    {
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        Sanctum::actingAs($admin);
        $other = User::factory()->create(['role' => 'ADMIN_SISTEMA', 'authorization_reference' => 'Oficio 1']);

        $this->putJson("/api/users/{$other->id}", [
            'name' => $other->name, 'email' => $other->email,
            'role' => 'ADMIN_SISTEMA', 'status' => 'INACTIVO',
            'authorization_reference' => 'Oficio 1',
        ])->assertOk();
        $this->assertDatabaseHas('users', ['id' => $other->id, 'status' => 'INACTIVO']);
    }

    /* ───── CU06-E4: reenviar correo de activación ───── */

    public function test_admin_can_resend_activation_email(): void
    {
        \Illuminate\Support\Facades\Notification::fake();
        $admin = User::factory()->create(['role' => 'ADMIN_SISTEMA']);
        $target = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($admin);

        $this->postJson("/api/users/{$target->id}/resend-activation")->assertOk();
        \Illuminate\Support\Facades\Notification::assertSentTo($target, \App\Notifications\AccountActivationNotification::class);
    }

    public function test_non_admin_cannot_resend_activation(): void
    {
        $lider = User::factory()->create(['role' => 'LIDER_SEMILLERO']);
        $target = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($lider);

        $this->postJson("/api/users/{$target->id}/resend-activation")->assertStatus(403);
    }

}