<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class NotificacionRead extends Model
{
    protected $table = 'notificacion_reads';

    protected $fillable = ['notificacion_id', 'user_id', 'read_at'];
}
