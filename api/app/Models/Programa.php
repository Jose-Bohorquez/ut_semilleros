<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Programa extends Model
{
    protected $table = 'programas';
    protected $fillable = ['nombre', 'facultad_id', 'nivel', 'modalidad'];

    public function facultad()
    {
        return $this->belongsTo(Facultad::class);
    }
}
