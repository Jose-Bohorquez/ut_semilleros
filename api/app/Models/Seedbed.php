<?php
// #archivo: /backend/app/Models/Seedbed.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seedbed extends Model
{

    protected $fillable = [
        'name',
        'description',
        'program_id',
        'area_id',
        'status'
    ];

    /**
     * Relación con programa
     */
    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    /**
     * Área de conocimiento (RF05: todo semillero tiene al menos un área)
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }


    /**
     * Relación con usuarios (integrantes del semillero)
     */
    public function users()
    {
        return $this->belongsToMany(
            User::class,
            'seedbed_user'
        )
        ->withPivot('role')
        ->withTimestamps();
    }



    /**
 * Proyectos del semillero
 */
public function projects()
{
    return $this->hasMany(Project::class);
}




}