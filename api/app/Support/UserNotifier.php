<?php

namespace App\Support;

use App\Models\Notificacion;
use Illuminate\Support\Facades\Log;

/**
 * Avisa a UN usuario de algo que le pasó en el sistema (su solicitud fue resuelta, su propuesta fue evaluada):
 * crea la notificación interna (aparece en la campana) y manda el push a sus dispositivos suscritos.
 * Nunca lanza: el aviso es un efecto secundario y no puede revertir ni romper la operación.
 */
class UserNotifier
{
    public static function notify(int $userId, string $title, string $message, ?int $createdBy = null, string $pushUrl = '/notifications'): void
    {
        try {
            Notificacion::create([
                'title'        => $title,
                'message'      => $message,
                'type'         => 'ANUNCIO',
                'created_by'   => $createdBy ?? $userId,
                'target_type'  => 'USER',
                'target_value' => (string) $userId,
            ]);

            // Encolado: la operación que avisa (aprobar, evaluar) no espera la entrega del push.
            app(PushSender::class)->queue([$userId], ['title' => $title, 'body' => $message, 'url' => $pushUrl]);
        } catch (\Throwable $e) {
            try {
                Log::warning('[Notificación] No se pudo avisar al usuario', ['user_id' => $userId, 'error' => $e->getMessage()]);
            } catch (\Throwable) {
            }
        }
    }
}
