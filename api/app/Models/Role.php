<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    // 🧩 Campos que se pueden llenar masivamente
    protected $fillable = [
        'nombre',
        'descripcion',
    ];

    // 🔗 Relación con usuarios (1 rol tiene muchos usuarios)
    public function users()
    {
        return $this->hasMany(User::class);
    }
}
