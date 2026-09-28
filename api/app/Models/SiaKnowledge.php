<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/* Respuesta corregida por el administrador a partir del feedback de SIA. */
class SiaKnowledge extends Model
{
    protected $table = 'sia_knowledge';

    protected $fillable = ['question', 'answer', 'active', 'created_by'];

    protected $casts = ['active' => 'boolean'];
}
