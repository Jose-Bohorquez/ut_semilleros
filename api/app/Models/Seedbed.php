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
        'program_id',
        'group_id',
        'cat_id',
        'coordinator_id',
        'area_id',
        'mision',
        'vision',
        'justificacion',
        'objetivo_general',
        'authorization_reference',
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