<?php // archivo: backend/app/models/project.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Project extends Model
{

    protected $fillable = [
        'seedbed_id',
        'title',
        'description',
        'status'
    ];

    /**
     * Relación con semillero
     */
    public function seedbed()
    {
        return $this->belongsTo(Seedbed::class);
    }

    /**
     * Miembros del proyecto
     */
/**
 * Relación con miembros del proyecto
 */
public function users()
{
    return $this->belongsToMany(
        User::class,
        'project_members'
    )
    ->withPivot('role')
    ->withTimestamps();
}

}