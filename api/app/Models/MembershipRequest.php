<?php

// #archivo: backend/app/Models/MembershipRequest.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MembershipRequest extends Model
{
    /**
     * Tabla asociada.
     *
     * IMPORTANTE:
     * La tabla sigue llamándose "requests"
     * para no romper migraciones existentes.
     */
    protected $table = 'requests';

    /**
     * Campos asignables masivamente.
     */
    protected $fillable = [

        'user_id',

        'seedbed_id',

        'status'

    ];

    /**
     * Usuario que realiza la solicitud.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Semillero solicitado.
     */
    public function seedbed()
    {
        return $this->belongsTo(Seedbed::class);
    }
}