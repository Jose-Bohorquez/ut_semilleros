<?php
// #archivo: /backend/app/Models/PushSubscription.php
// Modelo que faltaba por crear — la tabla y el controlador ya existían,
// pero sin este archivo cualquier operación con push_subscriptions
// fallaba con "Class App\Models\PushSubscription not found" (2026-07-28).

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
    protected $fillable = [
        'user_id',
        'endpoint',
        'p256dh_key',
        'auth_token',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
