<?php // #archivo: /backend/html/app/Models/Faculty.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Faculty
 *
 * Representa las facultades de la universidad.
 * Se relaciona posteriormente con:
 * - programas académicos
 * - semilleros
 *
 * Alineado con:
 * RF02 - Gestión de Facultades
 */
class Faculty extends Model
{

    /**
     * Campos que pueden ser asignados masivamente
     */
    protected $fillable = [
        'name',
        'status'
    ];

}