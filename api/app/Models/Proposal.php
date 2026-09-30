<?php #archivo: backend/app/Models/Proposal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{

    protected $fillable = [
        'user_id',
        'program_id',
        'title',
        'description',
        'phone',
        'status'
    ];

    /* RNF03: dato personal cifrado en reposo con APP_KEY, mismo patrón que
       Coordinator/SeedbedMember. */
    protected function casts(): array
    {
        return [
            'phone' => 'encrypted',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Áreas de conocimiento (CU25: selección múltiple, antes area_id único).
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'proposal_area');
    }

}