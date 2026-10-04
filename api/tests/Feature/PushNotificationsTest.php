<?php

namespace Tests\Feature;

use App\Models\Area;
use App\Models\Faculty;
use App\Models\MembershipRequest;
use App\Models\Notificacion;
use App\Models\Program;
use App\Models\Proposal;
use App\Models\PushSubscription;
use App\Models\Seedbed;
use App\Models\User;
use App\Support\PushSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/** Envío de notificaciones push: prueba de extremo a extremo, avisos automáticos al estudiante y tolerancia a fallos. */
class PushNotificationsTest extends TestCase
{
    use RefreshDatabase;

    /** Sustituye la entrega real (red) por resultados controlados y registra qué se intentó enviar. */
    private function fakeSender(array $results = [], bool $throws = false): object
    {
        $fake = new class($results, $throws) extends PushSender {
            public array $deliveries = [];

            public function __construct(private array $results, private bool $throws) {}

            protected function deliver($subs, string $payload): iterable
            {
                if ($this->throws) {
                    throw new \RuntimeException('el servicio de push no responde');
                }
                $this->deliveries[] = ['users' => $subs->pluck('user_id')->all(), 'payload' => json_decode($payload, true)];
                foreach ($subs as $s) {
                    yield $this->results[$s->endpoint] ?? ['endpoint' => $s->endpoint, 'success' => true, 'expired' => false, 'reason' => ''];
                }
            }
        };
        $this->app->instance(PushSender::class, $fake);
        config(['services.webpush.public_key' => 'pub', 'services.webpush.private_key' => 'priv']);

        return $fake;
    }

    private function user(string $role): User
    {
        return User::factory()->create(['role' => $role, 'status' => 'ACTIVO']);
    }

    private function subscribe(User $u, ?string $endpoint = null): PushSubscription
    {
        return PushSubscription::create([
            'user_id' => $u->id, 'endpoint' => $endpoint ?? 'https://push.example/' . uniqid(),
            'p256dh_key' => 'k', 'auth_token' => 'a',
        ]);
    }

    private function seedbed(): Seedbed
    {
        $f = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $p = Program::create(['name' => 'P', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
        $s = Seedbed::create(['name' => 'Semillero de prueba', 'status' => 'ACTIVO']);
        $s->programs()->attach($p->id);

        return $s;
    }

    /* ── Prueba de extremo a extremo ── */

    public function test_the_test_endpoint_requires_a_session(): void
    {
        $this->postJson('/api/push-subscriptions/test')->assertStatus(401);
    }

    public function test_without_a_subscribed_device_the_test_explains_what_to_do(): void
    {
        $this->fakeSender();
        Sanctum::actingAs($this->user('ESTUDIANTE'));

        $res = $this->postJson('/api/push-subscriptions/test')->assertStatus(409);

        $this->assertSame(0, $res->json('subscriptions'));
        $this->assertStringContainsString('todavía no está suscrito', $res->json('message'));
    }

    public function test_the_test_push_goes_only_to_the_devices_of_the_user_who_asks(): void
    {
        $fake = $this->fakeSender();
        $me = $this->user('ESTUDIANTE'); $other = $this->user('ESTUDIANTE');
        $this->subscribe($me); $this->subscribe($me); $this->subscribe($other);
        Sanctum::actingAs($me);

        $res = $this->postJson('/api/push-subscriptions/test')->assertOk();

        $this->assertSame(2, $res->json('sent'));
        $this->assertSame([$me->id, $me->id], $fake->deliveries[0]['users']);
        $this->assertSame('Notificación de prueba', $fake->deliveries[0]['payload']['title']);
    }

    public function test_expired_subscriptions_are_removed_and_reported(): void
    {
        $me = $this->user('ESTUDIANTE');
        $live = $this->subscribe($me, 'https://push.example/vivo');
        $dead = $this->subscribe($me, 'https://push.example/muerto');
        $this->fakeSender(['https://push.example/muerto' => ['endpoint' => 'https://push.example/muerto', 'success' => false, 'expired' => true, 'reason' => 'gone']]);
        Sanctum::actingAs($me);

        $res = $this->postJson('/api/push-subscriptions/test')->assertOk();

        $this->assertSame([1, 1], [$res->json('sent'), $res->json('expired')]);
        $this->assertDatabaseMissing('push_subscriptions', ['id' => $dead->id]);
        $this->assertDatabaseHas('push_subscriptions', ['id' => $live->id]);
    }

    public function test_a_delivery_failure_is_reported_with_502_instead_of_a_server_error(): void
    {
        $me = $this->user('ESTUDIANTE');
        $this->subscribe($me, 'https://push.example/x');
        $this->fakeSender(['https://push.example/x' => ['endpoint' => 'https://push.example/x', 'success' => false, 'expired' => false, 'reason' => 'Unauthorized: invalid VAPID']]);
        Sanctum::actingAs($me);

        $res = $this->postJson('/api/push-subscriptions/test')->assertStatus(502);

        $this->assertSame(1, $res->json('failed'));
        $this->assertStringContainsString('invalid VAPID', $res->json('errors.0'));
    }

    public function test_without_vapid_keys_the_test_says_so(): void
    {
        $me = $this->user('ESTUDIANTE');
        $this->subscribe($me);
        config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);
        Sanctum::actingAs($me);

        $res = $this->postJson('/api/push-subscriptions/test')->assertStatus(502);

        $this->assertStringContainsString('VAPID', $res->json('errors.0'));
    }

    /* ── Anuncios manuales ── */

    public function test_a_manual_announcement_reaches_only_the_target_audience(): void
    {
        $fake = $this->fakeSender();
        $admin = $this->user('ADMIN_SISTEMA'); $student = $this->user('ESTUDIANTE'); $adm = $this->user('ADMINISTRATIVO');
        $this->subscribe($student); $this->subscribe($adm);
        Sanctum::actingAs($admin);

        $this->postJson('/api/notifications', [
            'title' => 'Reunión', 'message' => 'Mañana a las 10', 'type' => 'ANUNCIO',
            'target_type' => 'ROLE', 'target_value' => 'ESTUDIANTE',
        ])->assertStatus(201);

        $this->assertSame([$student->id], $fake->deliveries[0]['users']);
        $this->assertSame('Reunión', $fake->deliveries[0]['payload']['title']);
    }

    public function test_a_push_failure_does_not_break_sending_an_announcement(): void
    {
        $this->fakeSender([], true);
        $student = $this->user('ESTUDIANTE'); $this->subscribe($student);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->postJson('/api/notifications', [
            'title' => 'Aviso', 'message' => 'Texto', 'type' => 'ANUNCIO', 'target_type' => 'ALL',
        ])->assertStatus(201);

        $this->assertDatabaseHas('notificaciones', ['title' => 'Aviso']);
    }

    /* ── Avisos automáticos al estudiante ── */

    private function pendingRequest(User $student, Seedbed $s): MembershipRequest
    {
        return MembershipRequest::create([
            'user_id' => $student->id, 'seedbed_id' => $s->id, 'program_id' => $s->programs()->first()->id,
            'status' => 'PENDIENTE', 'phone' => '3001234567', 'message' => 'Quiero unirme al semillero por favor.',
        ]);
    }

    public function test_approving_a_request_notifies_the_student_in_the_bell_and_by_push(): void
    {
        $fake = $this->fakeSender();
        $student = $this->user('ESTUDIANTE'); $this->subscribe($student);
        $s = $this->seedbed(); $req = $this->pendingRequest($student, $s);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA', 'reason' => 'Bienvenido, nos vemos el jueves.'])->assertOk();

        $n = Notificacion::where('target_type', 'USER')->where('target_value', (string) $student->id)->firstOrFail();
        $this->assertSame('Tu solicitud fue aprobada', $n->title);
        $this->assertStringContainsString('Semillero de prueba', $n->message);
        $this->assertStringContainsString('jueves', $n->message);
        $this->assertSame([$student->id], $fake->deliveries[0]['users']);
        $this->assertSame('/requests', $fake->deliveries[0]['payload']['url']);

        // y el estudiante lo ve en su campana
        Sanctum::actingAs($student);
        $this->getJson('/api/notifications')->assertOk()->assertJsonFragment(['title' => 'Tu solicitud fue aprobada']);
    }

    public function test_rejecting_a_request_notifies_with_the_reason(): void
    {
        $this->fakeSender();
        $student = $this->user('ESTUDIANTE');
        $s = $this->seedbed(); $req = $this->pendingRequest($student, $s);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'RECHAZADA', 'reason' => 'No hay cupo este semestre.'])->assertOk();

        $n = Notificacion::where('target_value', (string) $student->id)->firstOrFail();
        $this->assertSame('Tu solicitud no fue aprobada', $n->title);
        $this->assertStringContainsString('No hay cupo', $n->message);
    }

    public function test_a_push_failure_never_blocks_resolving_a_request(): void
    {
        $this->fakeSender([], true);
        $student = $this->user('ESTUDIANTE'); $this->subscribe($student);
        $s = $this->seedbed(); $req = $this->pendingRequest($student, $s);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA'])->assertOk();

        $this->assertSame('APROBADA', $req->fresh()->status);
    }

    public function test_evaluating_a_proposal_notifies_the_student(): void
    {
        $fake = $this->fakeSender();
        $student = $this->user('ESTUDIANTE'); $this->subscribe($student);
        $f = Faculty::create(['name' => 'F2', 'status' => 'ACTIVO']);
        $program = Program::create(['name' => 'P2', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
        $p = Proposal::create([
            'user_id' => $student->id, 'program_id' => $program->id, 'title' => 'Sensores de agua',
            'description' => 'Descripción de la propuesta con suficiente detalle para evaluarla.', 'phone' => '3001234567', 'status' => 'RECIBIDA',
        ]);
        $p->areas()->attach(Area::create(['name' => 'A', 'code' => 'A1', 'status' => 'ACTIVO'])->id);
        Sanctum::actingAs($this->user('ADMINISTRATIVO'));

        $this->putJson("/api/proposals/{$p->id}/update-status", ['status' => 'ARCHIVADA', 'review_note' => 'No encaja con las líneas actuales.'])->assertOk();

        $n = Notificacion::where('target_value', (string) $student->id)->firstOrFail();
        $this->assertSame('Tu propuesta fue archivada', $n->title);
        $this->assertStringContainsString('Sensores de agua', $n->message);
        $this->assertStringContainsString('No encaja', $n->message);
        $this->assertSame('/proposals', $fake->deliveries[0]['payload']['url']);
    }
}
