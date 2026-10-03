<?php

namespace App\Support;

use App\Models\PushSubscription;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

/**
 * Envío de notificaciones push (Web Push) a todos los dispositivos suscritos de un conjunto de usuarios.
 *
 * Nunca lanza: un fallo de push no debe tumbar la operación que lo originó (aprobar una solicitud, enviar
 * un anuncio…). Devuelve un resumen para poder diagnosticar (`POST /push-subscriptions/test`).
 * Las suscripciones que el servicio de push declara caducadas (410/404) se borran solas.
 */
class PushSender
{
    /** @return array{subscriptions:int,sent:int,failed:int,expired:int,errors:string[]} */
    public function send(iterable $userIds, array $payload): array
    {
        $ids  = collect($userIds)->filter()->unique()->values();
        $subs = $ids->isEmpty() ? collect() : PushSubscription::whereIn('user_id', $ids)->get();

        $summary = ['subscriptions' => $subs->count(), 'sent' => 0, 'failed' => 0, 'expired' => 0, 'errors' => []];
        if ($subs->isEmpty()) {
            return $summary;
        }

        if (! config('services.webpush.public_key') || ! config('services.webpush.private_key')) {
            Log::warning('[Push] Claves VAPID sin configurar: no se envía.');
            $summary['failed']   = $subs->count();
            $summary['errors'][] = 'Las claves VAPID no están configuradas en el servidor.';

            return $summary;
        }

        try {
            foreach ($this->deliver($subs, json_encode($payload, JSON_UNESCAPED_UNICODE)) as $r) {
                if ($r['success']) {
                    $summary['sent']++;
                } elseif ($r['expired']) {
                    PushSubscription::where('endpoint', $r['endpoint'])->delete();
                    $summary['expired']++;
                } else {
                    $summary['failed']++;
                    $summary['errors'][] = Str::limit((string) $r['reason'], 140);
                    Log::warning('[Push] Falló el envío a una suscripción', ['endpoint' => Str::limit($r['endpoint'], 80), 'reason' => $r['reason']]);
                }
            }
        } catch (\Throwable $e) {
            Log::error('[Push] Error al enviar', ['error' => $e->getMessage()]);
            $summary['failed']  += max(0, $summary['subscriptions'] - $summary['sent'] - $summary['expired'] - $summary['failed']);
            $summary['errors'][] = 'Error interno al enviar: ' . Str::limit($e->getMessage(), 120);
        }

        return $summary;
    }

    /**
     * Entrega real. Se aísla en un método para sustituirlo en las pruebas (sin red).
     *
     * @return iterable<array{endpoint:string,success:bool,expired:bool,reason:string}>
     */
    protected function deliver($subs, string $payload): iterable
    {
        $webPush = new WebPush(['VAPID' => [
            'subject'    => config('services.webpush.subject'),
            'publicKey'  => config('services.webpush.public_key'),
            'privateKey' => config('services.webpush.private_key'),
        ]]);

        foreach ($subs as $sub) {
            $webPush->queueNotification(Subscription::create([
                'endpoint' => $sub->endpoint,
                'keys'     => ['p256dh' => $sub->p256dh_key, 'auth' => $sub->auth_token],
            ]), $payload);
        }

        foreach ($webPush->flush() as $report) {
            yield [
                'endpoint' => (string) $report->getRequest()->getUri(),
                'success'  => $report->isSuccess(),
                'expired'  => $report->isSubscriptionExpired(),
                'reason'   => (string) $report->getReason(),
            ];
        }
    }
}
