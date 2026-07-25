<?php # archivo: 📁 backend/app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{

    protected $fillable = [
        'project_id',
        'type',
        'title',
        'year',
        'url'
    ];


    /**
     * Relación con proyecto
     */
    public function project()
    {
        return $this->belongsTo(Project::class);
    }

}