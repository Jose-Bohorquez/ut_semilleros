<?php

namespace Tests\Feature\Request;

use Tests\TestCase;
use App\Models\User;
use App\Models\Faculty;
use App\Models\Program;
use App\Models\Seedbed;
use App\Models\MembershipRequest;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Auditoría 2 — CU22/CU23: teléfono cifrado (CU22-H1) y estado inicial
 * siempre PENDIENTE (CU22-H2).
 */
class Audit2_Cu22RequestTest extends TestCase
{
    use RefreshDatabase;

    private function seedbed(): Seedbed
    {
        $faculty = Faculty::create(['name' => 'Facultad Test', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'Programa Test', 'faculty_id' => $faculty->id, 'status' => 'ACTIVO']);
        $seedbed = Seedbed::create(['name' => 'Semillero Test', 'status' => 'ACTIVO']);
        $seedbed->programs()->attach($program->id);
        return $seedbed;
    }

    private function payload(Seedbed $seedbed, array $extra = []): array
    {
        return array_merge([
            'seedbed_id' => $seedbed->id,
            'program_id' => $seedbed->programs()->first()->id,
            'phone'      => '3001234567',
            'message'    => 'Mensaje de prueba con longitud suficiente para la validación.',
        ], $extra);
    }

    /* ── CU22-H1 ── */

    public function test_phone_is_encrypted_at_rest_and_visible_to_owner_and_admin(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $seedbed = $this->seedbed();
        Sanctum::actingAs($student);

        $res = $this->postJson('/api/requests', $this->payload($seedbed, ['user_id' => $student->id]))->assertStatus(201);
        $id  = $res->json('request.id');

        $raw = DB::table('requests')->where('id', $id)->value('phone');
        $this->assertNotSame('3001234567', $raw);
        $this->assertStringNotContainsString('3001234567', $raw);
        $this->assertSame('3001234567', Crypt::decryptString($raw));

        // El estudiante lo ve descifrado en «Mis solicitudes» (CU23).
        $this->getJson('/api/requests/my')->assertStatus(200)
            ->assertJsonPath('requests.0.phone', '3001234567');

        // CU24 paso 4: el administrador lo ve descifrado en el detalle.
        Sanctum::actingAs(User::factory()->create(['role' => 'ADMIN_SISTEMA']));
        $this->getJson("/api/requests/{$id}")->assertStatus(200)
            ->assertJsonPath('request.phone', '3001234567');
    }

    public function test_migration_encrypts_existing_plain_phones_idempotently(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        $seedbed = $this->seedbed();
        $plainId = DB::table('requests')->insertGetId([
            'user_id' => $student->id, 'seedbed_id' => $seedbed->id, 'status' => 'PENDIENTE',
            'phone' => '3009998877', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $encId = DB::table('requests')->insertGetId([
            'user_id' => $student->id, 'seedbed_id' => $seedbed->id, 'status' => 'RECHAZADA',
            'phone' => Crypt::encryptString('3115556677'), 'created_at' => now(), 'updated_at' => now(),
        ]);
        $encBefore = DB::table('requests')->where('id', $encId)->value('phone');

        $migration = require base_path('database/migrations/2026_10_01_000001_cu22_encrypt_request_phone.php');
        $migration->up();
        $migration->up(); // idempotente

        $plainRaw = DB::table('requests')->where('id', $plainId)->value('phone');
        $this->assertNotSame('3009998877', $plainRaw);
        $this->assertSame('3009998877', Crypt::decryptString($plainRaw));
        $this->assertSame($encBefore, DB::table('requests')->where('id', $encId)->value('phone'), 'No se re-cifra lo ya cifrado.');
        $this->assertSame('3009998877', MembershipRequest::find($plainId)->phone);

        $migration->down();
        $this->assertSame('3009998877', DB::table('requests')->where('id', $plainId)->value('phone'));
    }

    /* ── CU22-H2 ── */

    public function test_staff_cannot_create_request_already_approved(): void
    {
        $seedbed = $this->seedbed();
        foreach (['LIDER_SEMILLERO', 'ADMINISTRATIVO', 'ADMIN_SISTEMA'] as $role) {
            $student = User::factory()->create(['role' => 'ESTUDIANTE']);
            Sanctum::actingAs(User::factory()->create(['role' => $role]));

            $res = $this->postJson('/api/requests', $this->payload($seedbed, [
                'user_id' => $student->id, 'status' => 'APROBADA',
            ]));
            if ($res->status() === 403) continue; // la ruta puede restringir el rol; lo importante es que nunca nazca APROBADA
            $res->assertStatus(201);
            $this->assertDatabaseHas('requests', ['user_id' => $student->id, 'status' => 'PENDIENTE']);
        }
        $this->assertDatabaseMissing('requests', ['status' => 'APROBADA']);
    }

    public function test_status_is_optional_on_create(): void
    {
        $student = User::factory()->create(['role' => 'ESTUDIANTE']);
        Sanctum::actingAs($student);
        $this->postJson('/api/requests', $this->payload($this->seedbed(), ['user_id' => $student->id]))->assertStatus(201);
        $this->assertDatabaseHas('requests', ['user_id' => $student->id, 'status' => 'PENDIENTE']);
    }
}
