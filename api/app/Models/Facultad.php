<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Facultad extends Model
{
    use HasFactory;

    // 👇 Indica el nombre correcto de la tabla en la base de datos
    protected $table = 'facultades';

    protected $fillable = ['nombre', 'descripcion'];

    public function programas()
    {
        return $this->hasMany(Programa::class);
    }
}
