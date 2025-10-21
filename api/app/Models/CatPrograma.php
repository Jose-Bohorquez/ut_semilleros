<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatPrograma extends Model
{
    use HasFactory;

    // 👇 Forzamos el nombre correcto de la tabla
    protected $table = 'cat_programa';

    protected $fillable = [
        'centro_tutorial_id',
        'programa_id',
        'jornada',
        'modalidad',
    ];

    // 🔗 Relaciones
    public function centroTutorial()
    {
        return $this->belongsTo(CentroTutorial::class);
    }

    public function programa()
    {
        return $this->belongsTo(Programa::class);
    }
}
