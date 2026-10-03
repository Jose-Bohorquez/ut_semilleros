<?php

namespace Tests\Feature;

use App\Jobs\DeliverPushNotification;
use App\Models\Faculty;
use App\Models\MembershipRequest;
use App\Models\Notificacion;
use App\Models\Program;
use App\Models\PushSubscription;
use App\Models\Seedbed;
use App\Models\User;
use App\Notifications\AccountActivationNotification;
use App\Support\PushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Envíos asíncronos (cola `database` + cron de queue:work en producción): la petición que origina un aviso
 * no espera a la red, y cada entrega se reintenta 3 veces si falla (mismo criterio que E4 de CU04).
 */
class AsyncDeliveryTest extends TestCase
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
                $this->deliveries[] = ['subs' => $subs->pluck('id')->all(), 'payload' => json_decode($payload, true)];
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

    private function pendingRequest(User $student): MembershipRequest
    {
        $f = Faculty::create(['name' => 'F', 'status' => 'ACTIVO']);
        $p = Program::create(['name' => 'P', 'faculty_id' => $f->id, 'status' => 'ACTIVO']);
        $s = Seedbed::create(['name' => 'Semillero de prueba', 'status' => 'ACTIVO']);
        $s->programs()->attach($p->id);

        return MembershipRequest::create([
            'user_id' => $student->id, 'seedbed_id' => $s->id, 'program_id' => $p->id,
            'status' => 'PENDIENTE', 'phone' => '3001234567', 'message' => 'Quiero unirme al semillero por favor.',
        ]);
    }

    private function runJob(PushSubscription $sub): void
    {
        (new DeliverPushNotification($sub->id, ['title' => 'T', 'body' => 'B', 'url' => '/']))->handle(app(PushSender::class));
    }

    /* ── La petición no espera a la red ── */

    public function test_resolving_a_request_queues_one_delivery_per_device_without_delivering_in_the_request(): void
    {
        Queue::fake();
        $fake = $this->fakeSender();
        $student = $this->user('ESTUDIANTE');
        $a = $this->subscribe($student); $b = $this->subscribe($student);
        $req = $this->pendingRequest($student);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'APROBADA', 'reason' => 'Bienvenido.'])->assertOk();

        $this->assertSame([], $fake->deliveries, 'la entrega no debe ocurrir dentro de la petición');
        Queue::assertPushed(DeliverPushNotification::class, 2);
        Queue::assertPushed(fn (DeliverPushNotification $j) => $j->subscriptionId === $a->id && $j->payload['url'] === '/requests');
        Queue::assertPushed(fn (DeliverPushNotification $j) => $j->subscriptionId === $b->id);
        // La campana no depende de la cola: la notificación interna ya existe.
        $this->assertTrue(Notificacion::where('target_type', 'USER')->where('target_value', (string) $student->id)->exists());
    }

    public function test_an_announcement_queues_only_the_devices_of_the_audience(): void
    {
        Queue::fake();
        $this->fakeSender();
        $s1 = $this->user('ESTUDIANTE'); $s2 = $this->user('ESTUDIANTE'); $adm = $this->user('ADMINISTRATIVO');
        $d1 = $this->subscribe($s1); $d2 = $this->subscribe($s2); $this->subscribe($adm);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->postJson('/api/notifications', [
            'title' => 'Reunión', 'message' => 'Mañana a las 10', 'type' => 'ANUNCIO',
            'target_type' => 'ROLE', 'target_value' => 'ESTUDIANTE',
        ])->assertStatus(201);

        Queue::assertPushed(DeliverPushNotification::class, 2);
        $queued = Queue::pushed(DeliverPushNotification::class)->map(fn ($j) => $j->subscriptionId)->sort()->values()->all();
        $this->assertSame([$d1->id, $d2->id], $queued);
    }

    public function test_the_diagnostic_test_push_is_still_delivered_immediately(): void
    {
        Queue::fake();
        $fake = $this->fakeSender();
        $me = $this->user('ESTUDIANTE'); $this->subscribe($me);
        Sanctum::actingAs($me);

        $this->postJson('/api/push-subscriptions/test')->assertOk();

        $this->assertCount(1, $fake->deliveries);
        Queue::assertNothingPushed();
    }

    /* ── El trabajo de entrega ── */

    public function test_the_delivery_job_retries_3_times_and_waits_for_the_commit(): void
    {
        $job = new DeliverPushNotification(1, []);

        $this->assertInstanceOf(ShouldQueue::class, $job);
        $this->assertSame(3, $job->tries);
        $this->assertSame([30, 120], $job->backoff);
        $this->assertTrue($job->afterCommit);
    }

    public function test_a_successful_delivery_finishes_the_job(): void
    {
        $fake = $this->fakeSender();
        $sub = $this->subscribe($this->user('ESTUDIANTE'));

        $this->runJob($sub);

        $this->assertSame([[$sub->id]], array_column($fake->deliveries, 'subs'));
    }

    public function test_a_failed_delivery_throws_so_the_queue_retries_it(): void
    {
        $sub = $this->subscribe($this->user('ESTUDIANTE'), 'https://push.example/x');
        $this->fakeSender(['https://push.example/x' => ['endpoint' => 'https://push.example/x', 'success' => false, 'expired' => false, 'reason' => '503 Service Unavailable']]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('503 Service Unavailable');
        $this->runJob($sub);
    }

    public function test_an_expired_subscription_is_removed_and_not_retried(): void
    {
        $sub = $this->subscribe($this->user('ESTUDIANTE'), 'https://push.example/muerto');
        $this->fakeSender(['https://push.example/muerto' => ['endpoint' => 'https://push.example/muerto', 'success' => false, 'expired' => true, 'reason' => 'gone']]);

        $this->runJob($sub);

        $this->assertDatabaseMissing('push_subscriptions', ['id' => $sub->id]);
    }

    public function test_without_vapid_keys_the_job_gives_up_without_retrying(): void
    {
        $fake = $this->fakeSender();
        config(['services.webpush.public_key' => null, 'services.webpush.private_key' => null]);
        $sub = $this->subscribe($this->user('ESTUDIANTE'));

        $this->runJob($sub);

        $this->assertSame([], $fake->deliveries);
    }

    public function test_a_device_unsubscribed_while_waiting_in_the_queue_is_skipped(): void
    {
        $fake = $this->fakeSender();
        $sub = $this->subscribe($this->user('ESTUDIANTE'));
        $job = new DeliverPushNotification($sub->id, ['title' => 'T']);
        $sub->delete();

        $job->handle(app(PushSender::class));

        $this->assertSame([], $fake->deliveries);
    }

    public function test_with_the_sync_queue_a_push_failure_still_does_not_break_the_operation(): void
    {
        // Producción hoy: QUEUE_CONNECTION=sync. El trabajo corre en el acto y lanza; no debe llegar al usuario.
        $this->fakeSender([], true);
        $student = $this->user('ESTUDIANTE'); $this->subscribe($student); $this->subscribe($student);
        $req = $this->pendingRequest($student);
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->putJson("/api/requests/{$req->id}/update-status", ['status' => 'RECHAZADA', 'reason' => 'Cupo completo este semestre.'])->assertOk();

        $this->assertSame('RECHAZADA', $req->fresh()->status);
    }

    /* ── Correo de activación ── */

    public function test_the_activation_mail_is_queued_with_retries(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->user('ADMIN_SISTEMA'));

        $this->postJson('/api/users', [
            'name' => 'Pablo E Cuenca', 'email' => 'nuevo@ut.edu.co', 'role' => 'LIDER_SEMILLERO',
            'status' => 'ACTIVO', 'authorization_reference' => 'Oficio 001 de 2026',
        ])->assertSuccessful();

        Notification::assertSentTo(
            User::where('email', 'nuevo@ut.edu.co')->first(),
            AccountActivationNotification::class,
            fn ($n) => $n instanceof ShouldQueue && $n->tries === 3 && $n->afterCommit === true
        );
    }
}
