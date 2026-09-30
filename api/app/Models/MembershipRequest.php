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

        'program_id',

        'status',

        'phone',

        'message',

        'reason'

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

    /** CU22: programa (de los activos del semillero) con el que se postula. */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}