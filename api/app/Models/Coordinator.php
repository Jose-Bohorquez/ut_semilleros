<?php #archivo: backend/app/Models/Coordinator.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Coordinator extends Model
{
        protected $fillable = [
        'name',
        'document',
        'email',
        'phone',
        'status'
    ];

    /* RNF03 / RNF12: dato personal cifrado en reposo con APP_KEY. Si se pierde
       o cambia APP_KEY, estos teléfonos no se pueden leer: respaldar el .env. */
    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
        ];
    }
}
