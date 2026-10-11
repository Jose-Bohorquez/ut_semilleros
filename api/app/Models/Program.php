<?php
// #archivo: /backend/app/Models/Program.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{

    protected $fillable = [
        'code',
        'name',
        'type',
        'area_tematica',
        'faculty_id',
        'status'
    ];

    /**
     * Relación con facultad
     */
    public function faculty()
    {
        return $this->belongsTo(Faculty::class);
    }

    /** CU08-A1: "información relacionada" en el detalle. CU13 Ronda B: un
     *  semillero puede tener varios programas, se pasó a muchos-a-muchos. */
    public function seedbeds()
    {
        return $this->belongsToMany(Seedbed::class, 'seedbed_program');
    }

}