<?php

namespace App\Jobs;

use App\Models\PushSubscription;
use App\Support\PushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Entrega un aviso push a UN dispositivo, fuera de la petición que lo originó (cola `database` + cron de
 * `queue:work`, ver docs/manuales/manual_tecnico.md §5). Un trabajo por dispositivo: si uno falla, el reintento
 * no repite el aviso en los que ya lo recibieron.
 *
 * 3 intentos (espera de 30 s y 2 min, mismo criterio que E4 de CU04). Suscripción caducada (404/410): se borra y no
 * se reintenta. Sin claves VAPID: se registra y no se reintenta. Agotados los intentos, queda en `failed_jobs`.
 */
class DeliverPushNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public int $timeout = 30;

    public function __construct(public int $subscriptionId, public array $payload)
    {
        // Si se despacha dentro de una transacción, espera al commit: no avisar de algo que se revirtió.
        $this->afterCommit();
    }

    public function handle(PushSender $sender): void
    {
        $sub = PushSubscription::find($this->subscriptionId);
        if (! $sub) {
            return; // el usuario desactivó las notificaciones mientras el aviso esperaba en la cola
        }

        $r = $sender->deliverTo($sub, $this->payload);
        if ($r === null || $r['success']) {
            return;
        }
        if ($r['expired']) {
            $sub->delete();

            return;
        }

        // Lanzar es lo que hace que la cola lo reintente.
        throw new \RuntimeException('Push no entregado: ' . Str::limit((string) $r['reason'], 140));
    }

    public function failed(\Throwable $e): void
    {
        Log::warning('[Push] Se agotaron los intentos de entrega', [
            'subscription_id' => $this->subscriptionId,
            'error'           => $e->getMessage(),
        ]);
    }
}
