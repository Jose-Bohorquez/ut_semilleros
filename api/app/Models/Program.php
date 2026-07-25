<?php
// #archivo: /backend/app/Models/Program.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model
{

    protected $fillable = [
        'name',
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

}