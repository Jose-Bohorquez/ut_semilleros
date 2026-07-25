<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Notificacion extends Model
{
    protected $table = 'notificaciones';

    protected $fillable = [
        'title',
        'message',
        'type',
        'created_by',
        'link',
        'target_type',
        'target_value',
    ];

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reads()
    {
        return $this->hasMany(NotificacionRead::class, 'notificacion_id');
    }
}
