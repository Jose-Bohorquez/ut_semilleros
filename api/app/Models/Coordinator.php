<?php #archivo: backend/app/Models/Coordinator.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
        protected $fillable = [
        'name',
        'email',
        'phone',
        'status'
    ];
}
