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

    /** CU08-A1: "información relacionada" en el detalle. */
    public function seedbeds()
    {
        return $this->hasMany(Seedbed::class);
    }

}