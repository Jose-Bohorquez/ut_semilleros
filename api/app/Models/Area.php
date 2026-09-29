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

    /** CU10-A1: "información relacionada" en el detalle. */
    public function seedbeds()
    {
        return $this->hasMany(Seedbed::class);
    }

    public function proposals()
    {
        return $this->hasMany(Proposal::class);
    }

}