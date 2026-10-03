<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PushSubscription;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'endpoint'                  => 'required|string',
            'keys.p256dh'               => 'required|string',
            'keys.auth'                 => 'required|string',
        ]);

        PushSubscription::updateOrCreate(
            ['user_id' => auth()->id(), 'endpoint' => $validated['endpoint']],
            [
                'p256dh_key'  => $validated['keys']['p256dh'],
                'auth_token'  => $validated['keys']['auth'],
            ]
        );

        return response()->json(['message' => 'Suscripción guardada'], 201);
    }

    public function destroy(Request $request)
    {
        PushSubscription::where('user_id', auth()->id())
            ->where('endpoint', $request->endpoint)
            ->delete();
        return response()->json(['message' => 'Suscripción eliminada']);
    }

    /**
     * POST /push-subscriptions/test — manda un push de prueba a los dispositivos del usuario autenticado y
     * devuelve qué pasó con cada uno. Sirve para comprobar de extremo a extremo (suscripción → servidor →
     * servicio de push del navegador → pantalla del teléfono) sin molestar a nadie más.
     */
    public function test()
    {
        $res = app(\App\Support\PushSender::class)->send([auth()->id()], [
            'title' => 'Notificación de prueba',
            'body'  => 'Si ves este aviso, las notificaciones push funcionan en este dispositivo.',
            'url'   => '/notifications',
        ]);

        if ($res['subscriptions'] === 0) {
            return response()->json($res + [
                'message' => 'Este dispositivo todavía no está suscrito. Activa las notificaciones y vuelve a intentarlo.',
            ], 409);
        }

        $ok = $res['sent'] > 0;

        return response()->json($res + [
            'message' => $ok
                ? 'Enviamos la notificación de prueba a ' . $res['sent'] . ' dispositivo(s). Debería aparecer en unos segundos.'
                : 'No se pudo entregar la notificación de prueba. Revisa el detalle.',
        ], $ok ? 200 : 502);
    }
}
