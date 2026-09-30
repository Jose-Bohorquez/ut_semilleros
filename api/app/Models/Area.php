<?php #archivo:backend/app/Models/Area.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Area extends Model
{

    protected $fillable = [
        'name',
        'code',
        'status'
    ];

    /** CU10-A1: "información relacionada" en el detalle. CU13 Ronda B: un
     *  semillero puede tener varias áreas, se pasó a muchos-a-muchos. */
    public function seedbeds()
    {
        return $this->belongsToMany(Seedbed::class, 'seedbed_area');
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

}