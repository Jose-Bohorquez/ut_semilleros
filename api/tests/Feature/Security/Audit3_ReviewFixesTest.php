<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Models\Area;
use App\Models\Cat;
use App\Models\Coordinator;
use App\Models\Faculty;
use App\Models\Group;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Revisión de seguridad del diff de la ronda de auditoría (2026-09-30): fugas que la
 * revisión independiente encontró después de las correcciones.
 */
class Audit3_ReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    private function seedbedWithLeader(): array
    {
        $faculty = Faculty::create(['name' => 'Facultad ' . uniqid(), 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa ' . uniqid(), 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $area    = Area::create(['name' => 'Área ' . uniqid(), 'code' => 'A-' . uniqid(), 'status' => 'ACTIVO']);
        $cat     = Cat::create([
            'name' => 'CAT ' . uniqid(), 'code' => 'C-' . uniqid(), 'address' => 'Calle 1 # 2-3',
            'email' => 'cat@ut.edu.co', 'phone1' => '3001112233', 'status' => 'ACTIVO',
        ]);
        $group = Group::create(['name' => 'Grupo ' . uniqid(), 'code' => 'G-' . uniqid(), 'status' => 'ACTIVO']);
        $coord = Coordinator::create([
            'name' => 'Coord', 'document' => uniqid(), 'email' => uniqid() . '@ut.edu.co',
            'phone' => '3005556677', 'status' => 'ACTIVO',
        ]);
        $seedbed = Seedbed::create([
            'code' => 'SB-' . uniqid(), 'name' => 'Semillero', 'objetivo_general' => 'objetivo largo de prueba',
            'authorization_reference' => 'Acta 9', 'status' => 'ACTIVO',
            'cat_id' => $cat->id, 'group_id' => $group->id, 'coordinator_id' => $coord->id,
        ]);
        $seedbed->programs()->attach($program->id);
        $seedbed->areas()->attach($area->id);
        $leader = User::factory()->create(['role' => 'LIDER_SEMILLERO', 'phone' => '3208889900']);
        $seedbed->users()->attach($leader->id, ['role' => 'LIDER']);

        return [$seedbed, $leader];
    }

    /* ── #2: el listado no debe devolver el modelo completo de los usuarios ── */

    public function test_staff_seedbed_list_does_not_leak_user_contact_data(): void
    {
        [$seedbed, $leader] = $this->seedbedWithLeader();

        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO', 'ADMIN_SISTEMA'] as $role) {
            Sanctum::actingAs($role === 'LIDER_SEMILLERO' ? $leader : User::factory()->create(['role' => $role]));

            foreach (['/api/seedbeds', "/api/seedbeds/{$seedbed->id}"] as $url) {
                $json = json_encode($this->getJson($url)->assertOk()->json());
                $this->assertStringNotContainsString($leader->email, $json, "$role $url");
                $this->assertStringNotContainsString('3208889900', $json, "$role $url");
            }
        }
    }

    public function test_staff_still_gets_the_leader_id_and_role_the_frontend_needs(): void
    {
        [$seedbed, $leader] = $this->seedbedWithLeader();
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));

        $u = $this->getJson("/api/seedbeds/{$seedbed->id}")->assertOk()->json('seedbed.users.0');
        $this->assertSame($leader->id, $u['id']);
        $this->assertSame('LIDER', $u['pivot']['role']);
        $this->assertSame($leader->name, $this->getJson("/api/seedbeds/{$seedbed->id}")->json('seedbed.leader_name'));
    }

    /* ── #5: el estudiante no recibe el CAT completo (dirección, correo, teléfonos) ── */

    public function test_student_gets_only_public_cat_and_group_fields(): void
    {
        [$seedbed] = $this->seedbedWithLeader();
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));

        foreach (['/api/seedbeds', "/api/seedbeds/{$seedbed->id}"] as $url) {
            $json = json_encode($this->getJson($url)->assertOk()->json());
            $this->assertStringNotContainsString('3001112233', $json, $url);   // phone1 del CAT
            $this->assertStringNotContainsString('Calle 1 # 2-3', $json, $url); // dirección del CAT
            $this->assertStringNotContainsString('cat@ut.edu.co', $json, $url);
        }
        $item = $this->getJson("/api/seedbeds/{$seedbed->id}")->json('seedbed');
        $this->assertNotEmpty($item['cat']['name']);
        $this->assertNotEmpty($item['group']['name']);
    }

    /* ── #3 / #4: la migración de teléfonos de solicitudes ── */

    private function insertRequest(?string $phone): int
    {
        [$seedbed] = $this->seedbedWithLeader();
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        return DB::table('requests')->insertGetId([
            'user_id' => $student->id, 'seedbed_id' => $seedbed->id, 'status' => 'PENDIENTE',
            'phone' => $phone, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    public function test_migration_turns_empty_phone_into_null_so_the_cast_can_read_it(): void
    {
        $emptyId = $this->insertRequest('');

        (require base_path('database/migrations/2026_10_01_000001_cu22_encrypt_request_phone.php'))->up();

        $this->assertNull(DB::table('requests')->where('id', $emptyId)->value('phone'));
        $this->assertNull(\App\Models\MembershipRequest::find($emptyId)->phone); // no lanza DecryptException
    }

    public function test_migration_aborts_instead_of_double_encrypting_a_value_from_another_app_key(): void
    {
        // Un payload con formato de Laravel (iv/value/mac) que esta clave no puede descifrar.
        $foreign = base64_encode(json_encode(['iv' => 'x', 'value' => 'y', 'mac' => 'z', 'tag' => '']));
        $id = $this->insertRequest($foreign);

        try {
            (require base_path('database/migrations/2026_10_01_000001_cu22_encrypt_request_phone.php'))->up();
            $this->fail('Debía abortar.');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('otra APP_KEY', $e->getMessage());
        }

        $this->assertSame($foreign, DB::table('requests')->where('id', $id)->value('phone'), 'El valor no se toca.');
    }

    public function test_migration_still_encrypts_a_plain_phone_and_leaves_valid_ciphertext_alone(): void
    {
        $plainId = $this->insertRequest('3001234567');
        $cipher  = Crypt::encryptString('3119998877');
        $encId   = $this->insertRequest($cipher);

        (require base_path('database/migrations/2026_10_01_000001_cu22_encrypt_request_phone.php'))->up();

        $this->assertSame('3001234567', Crypt::decryptString(DB::table('requests')->where('id', $plainId)->value('phone')));
        $this->assertSame($cipher, DB::table('requests')->where('id', $encId)->value('phone'));
    }

    /* ── #6: tope de tamaño de la foto antes de decodificarla ── */

    public function test_photo_payload_over_the_cap_is_rejected_before_decoding(): void
    {
        Sanctum::actingAs(User::factory()->create(['role' => 'ESTUDIANTE']));

        $this->postJson('/api/profile/photo', ['photo' => 'data:image/png;base64,' . str_repeat('A', 3_100_000)])
            ->assertStatus(422)->assertJsonValidationErrors(['photo']);
    }
}
