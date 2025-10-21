<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CentroTutorial extends Model
{
    use HasFactory;

    // 👇 Corrige el nombre de la tabla real
    protected $table = 'centros_tutoriales';

    protected $fillable = [
        'nombre',
        'direccion',
        'telefono',
        'email',
        'ciudad_id',
    ];

    // 🔗 Relación con ciudades
    public function ciudad()
    {
        return $this->belongsTo(Ciudad::class);
    }

    // 🔗 Relación con programas (muchos a muchos)
    public function programas()
    {
        return $this->belongsToMany(Programa::class, 'cat_programa');
    }
}
