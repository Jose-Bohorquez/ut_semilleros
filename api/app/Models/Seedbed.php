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
        'status',
        'inactivation_reason'
    ];

    /* CU16: campos calculados que se agregan al JSON del listado. */
    protected $appends = ['faculty_names', 'leader_name', 'members_count'];

    /* El conteo precalculado (withCount) es interno: se expone como
       `members_count` mediante el accessor. */
    protected $hidden = ['active_members_count'];

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
     * Integrantes reales del semillero (CU21, tabla seedbed_members). No
     * confundir con users(), que solo guarda al líder responsable.
     */
    public function members()
    {
        return $this->hasMany(SeedbedMember::class);
    }

    /**
 * Proyectos del semillero
 */
public function projects()
{
    return $this->hasMany(Project::class);
}

    /** CU15: solicitudes de ingreso al semillero. */
    public function requests()
    {
        return $this->hasMany(MembershipRequest::class);
    }

    /* CU16 paso 2: se calculan aquí (no en columnas) porque un semillero
       puede tener varios programas (Ronda B) — la facultad sale de ahí. */
    public function getFacultyNamesAttribute(): string
    {
        if (!$this->relationLoaded('programs')) return '';
        return $this->programs
            ->pluck('faculty.name')
            ->filter()
            ->unique()
            ->implode(', ');
    }

    public function getLeaderNameAttribute(): string
    {
        if (!$this->relationLoaded('users')) return '';
        $leader = $this->users->first(fn ($u) => $u->pivot->role === 'LIDER');
        return $leader?->name ?? 'Sin asignar';
    }

    public function getMembersCountAttribute(): int
    {
        /* CU16-H2 / T2: los integrantes son los seedbed_members ACTIVOS (CU21),
           no las filas de seedbed_user (que solo contienen al líder). */
        if (array_key_exists('active_members_count', $this->attributes)) {
            return (int) $this->attributes['active_members_count'];
        }
        return $this->exists ? $this->members()->where('status', 'ACTIVO')->count() : 0;
    }




}