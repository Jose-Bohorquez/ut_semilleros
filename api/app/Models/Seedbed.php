<?php
// #archivo: /backend/app/Models/Seedbed.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Seedbed extends Model
{

    protected $fillable = [
        'code',
        'name',
        'description',
        'group_id',
        'cat_id',
        'coordinator_id',
        'mision',
        'vision',
        'justificacion',
        'objetivo_general',
        'authorization_reference',
        'status'
    ];

    /**
     * Programas del semillero (CU13 Ronda B: selección múltiple, antes FK simple).
     */
    public function programs()
    {
        return $this->belongsToMany(Program::class, 'seedbed_program')->withTimestamps();
    }

    /**
     * Áreas de conocimiento del semillero (RF05: al menos una; CU13 Ronda B:
     * selección múltiple, antes FK simple).
     */
    public function areas()
    {
        return $this->belongsToMany(Area::class, 'seedbed_area')->withTimestamps();
    }

    /** CU13: grupo de investigación, CAT y coordinador del semillero. */
    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function cat()
    {
        return $this->belongsTo(Cat::class);
    }

    public function coordinator()
    {
        return $this->belongsTo(Coordinator::class);
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