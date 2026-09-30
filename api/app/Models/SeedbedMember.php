<?php # archivo: backend/app/Models/SeedbedMember.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SeedbedMember extends Model
{
    protected $fillable = [
        'seedbed_id',
        'user_id',
        'name',
        'student_code',
        'program_id',
        'level',
        'email',
        'address',
        'phone',
        'status',
        'inactivation_reason',
    ];

    /* RNF03 / RNF12: dato personal cifrado en reposo con APP_KEY, mismo
       patrón que Coordinator (CU12). Si se pierde o cambia APP_KEY, estos
       datos no se pueden leer: respaldar el .env. */
    protected function casts(): array
    {
        return [
            'address' => 'encrypted',
            'phone'   => 'encrypted',
        ];
    }

    public function seedbed()
    {
        return $this->belongsTo(Seedbed::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }
}
