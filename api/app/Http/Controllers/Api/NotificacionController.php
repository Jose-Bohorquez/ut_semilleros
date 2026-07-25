<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use App\Models\NotificacionRead;
use App\Models\PushSubscription;
use Illuminate\Http\Request;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;

class NotificacionController extends Controller
{

    /* ──────────────────────────────────────────────────────
     | HELPER: formatea una notificación para la respuesta
     | ─────────────────────────────────────────────────── */

    private function fmt(Notificacion $n, bool $isRead): array
    {
        return [
            'id'           => $n->id,
            'title'        => $n->title,
            'message'      => $n->message,
            'type'         => $n->type,
            'link'         => $n->link,
            'target_type'  => $n->target_type,
            'target_value' => $n->target_value,
            'created_at'   => $n->created_at->toIso8601String(),
            'sender_name'  => $n->creator->name ?? 'Sistema',
            'sender_photo' => $n->creator->profile_photo ?? null,
            'is_read'      => $isRead,
        ];
    }

    /* ──────────────────────────────────────────────────────
     | GET /notifications
     | Devuelve las notificaciones dirigidas al usuario actual
     | ─────────────────────────────────────────────────── */

    public function index()
    {
        $user       = auth()->user();
        $seedbedIds = $user->seedbeds()->pluck('seedbeds.id')
                          ->map(fn($id) => (string)$id)
                          ->toArray();

        $notifications = Notificacion::with('creator:id,name,profile_photo')
            ->where(function ($q) use ($user, $seedbedIds) {
                $q->where('target_type', 'ALL')
                  ->orWhere(fn($q) =>
                      $q->where('target_type', 'ROLE')
                        ->where('target_value', $user->role)
                  )
                  ->orWhere(fn($q) =>
                      $q->where('target_type', 'USER')
                        ->where('target_value', (string)$user->id)
                  );

                if (!empty($seedbedIds)) {
                    $q->orWhere(fn($q) =>
                        $q->where('target_type', 'SEEDBED')
                          ->whereIn('target_value', $seedbedIds)
                    );
                }
            })
            ->withExists([
                'reads as is_read' => fn($q) => $q->where('user_id', $user->id),
            ])
            ->latest()
            ->get();

        return response()->json([
            'notifications' => $notifications->map(
                fn($n) => $this->fmt($n, (bool)$n->is_read)
            ),
        ]);
    }

    /* ──────────────────────────────────────────────────────
     | GET /notifications/unread-count
     | ─────────────────────────────────────────────────── */

    public function unreadCount()
    {
        $user       = auth()->user();
        $seedbedIds = $user->seedbeds()->pluck('seedbeds.id')
                          ->map(fn($id) => (string)$id)
                          ->toArray();

        $total = Notificacion::where(function ($q) use ($user, $seedbedIds) {
                $q->where('target_type', 'ALL')
                  ->orWhere(fn($q) =>
                      $q->where('target_type', 'ROLE')->where('target_value', $user->role)
                  )
                  ->orWhere(fn($q) =>
                      $q->where('target_type', 'USER')->where('target_value', (string)$user->id)
                  );
                if (!empty($seedbedIds)) {
                    $q->orWhere(fn($q) =>
                        $q->where('target_type', 'SEEDBED')->whereIn('target_value', $seedbedIds)
                    );
                }
            })
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->count();

        return response()->json(['count' => $total]);
    }

    /* ──────────────────────────────────────────────────────
     | GET /notifications/sent
     | Historial de notificaciones enviadas por el usuario
     | ─────────────────────────────────────────────────── */

    public function sent()
    {
        $user  = auth()->user();
        $items = Notificacion::where('created_by', $user->id)
            ->latest()
            ->get();

        return response()->json([
            'notifications' => $items->map(fn($n) => $this->fmt($n, true)),
        ]);
    }

    /* ──────────────────────────────────────────────────────
     | POST /notifications
     | Crea y envía una notificación
     | ─────────────────────────────────────────────────── */

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'title'        => 'required|string|max:255',
            'message'      => 'required|string',
            'type'         => 'required|in:ANUNCIO,RECORDATORIO',
            'link'         => 'nullable|url|max:500',
            'target_type'  => 'required|in:ALL,ROLE,SEEDBED,USER',
            'target_value' => 'nullable|string|max:255',
        ]);

        /* ── Validación por rol ─────────────────────────── */

        if ($user->role === 'LIDER_SEMILLERO') {
            if (!in_array($validated['target_type'], ['SEEDBED', 'USER'])) {
                return response()->json([
                    'message' => 'El líder de semillero solo puede enviar notificaciones a su semillero o a integrantes específicos.',
                ], 403);
            }

            if ($validated['target_type'] === 'SEEDBED' && !empty($validated['target_value'])) {
                $owns = $user->seedbeds()
                             ->where('seedbeds.id', $validated['target_value'])
                             ->exists();
                if (!$owns) {
                    return response()->json([
                        'message' => 'Solo puedes enviar notificaciones a los semilleros a los que perteneces.',
                    ], 403);
                }
            }
        }

        $notif = Notificacion::create([
            'title'        => $validated['title'],
            'message'      => $validated['message'],
            'type'         => $validated['type'],
            'created_by'   => $user->id,
            'link'         => $validated['link'] ?? null,
            'target_type'  => $validated['target_type'],
            'target_value' => $validated['target_value'] ?? null,
        ]);

        $notif->load('creator:id,name,profile_photo');

        $this->dispatchPush($notif, $user);

        return response()->json([
            'message'      => 'Notificación enviada correctamente',
            'notification' => $this->fmt($notif, false),
        ], 201);
    }

    /* ──────────────────────────────────────────────────────
     | PUT /notifications/{id}/read
     | Marcar como leída
     | ─────────────────────────────────────────────────── */

    public function markRead($id)
    {
        $user = auth()->user();

        NotificacionRead::firstOrCreate([
            'notificacion_id' => $id,
            'user_id'         => $user->id,
        ], [
            'read_at' => now(),
        ]);

        return response()->json(['message' => 'Marcada como leída']);
    }

    /* ──────────────────────────────────────────────────────
     | PUT /notifications/read-all
     | Marcar todas como leídas
     | ─────────────────────────────────────────────────── */

    public function markAllRead()
    {
        $user       = auth()->user();
        $seedbedIds = $user->seedbeds()->pluck('seedbeds.id')
                          ->map(fn($id) => (string)$id)
                          ->toArray();

        $ids = Notificacion::where(function ($q) use ($user, $seedbedIds) {
                $q->where('target_type', 'ALL')
                  ->orWhere(fn($q) =>
                      $q->where('target_type', 'ROLE')->where('target_value', $user->role)
                  )
                  ->orWhere(fn($q) =>
                      $q->where('target_type', 'USER')->where('target_value', (string)$user->id)
                  );
                if (!empty($seedbedIds)) {
                    $q->orWhere(fn($q) =>
                        $q->where('target_type', 'SEEDBED')->whereIn('target_value', $seedbedIds)
                    );
                }
            })
            ->whereDoesntHave('reads', fn($q) => $q->where('user_id', $user->id))
            ->pluck('id');

        foreach ($ids as $nid) {
            NotificacionRead::firstOrCreate(
                ['notificacion_id' => $nid, 'user_id' => $user->id],
                ['read_at' => now()]
            );
        }

        return response()->json([
            'message' => 'Todas las notificaciones marcadas como leídas',
            'count'   => $ids->count(),
        ]);
    }

    /* ──────────────────────────────────────────────────────
     | HELPER: despacha push notifications a los suscritos
     | ─────────────────────────────────────────────────── */

    private function dispatchPush(Notificacion $notif, $sender): void
    {
        $auth = [
            'VAPID' => [
                'subject'    => config('services.webpush.subject'),
                'publicKey'  => config('services.webpush.public_key'),
                'privateKey' => config('services.webpush.private_key'),
            ],
        ];
        $webPush = new WebPush($auth);

        // Determinar qué user_ids reciben el push según target_type
        $userIds = match ($notif->target_type) {
            'ALL'     => \App\Models\User::pluck('id'),
            'ROLE'    => \App\Models\User::where('role', $notif->target_value)->pluck('id'),
            'USER'    => collect([$notif->target_value]),
            'SEEDBED' => \App\Models\User::whereHas('seedbeds', fn($q) =>
                             $q->where('seedbeds.id', $notif->target_value)
                         )->pluck('id'),
            default   => collect(),
        };

        $subscriptions = PushSubscription::whereIn('user_id', $userIds)->get();
        $payload = json_encode([
            'title' => $notif->title,
            'body'  => $notif->message,
            'url'   => $notif->link ?? '/',
        ]);

        foreach ($subscriptions as $sub) {
            $webPush->queueNotification(
                Subscription::create([
                    'endpoint' => $sub->endpoint,
                    'keys'     => [
                        'p256dh' => $sub->p256dh_key,
                        'auth'   => $sub->auth_token,
                    ],
                ]),
                $payload
            );
        }

        foreach ($webPush->flush() as $report) {
            if ($report->isSubscriptionExpired()) {
                PushSubscription::where('endpoint', $report->getRequest()->getUri()->__toString())->delete();
            }
        }
    }
}
