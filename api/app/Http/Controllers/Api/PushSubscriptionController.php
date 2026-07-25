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
}
