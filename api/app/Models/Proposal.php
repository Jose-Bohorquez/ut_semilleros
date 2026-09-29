<?php #archivo: backend/app/Models/Proposal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{

    protected $fillable = [
        'user_id',
        'area_id',
        'title',
        'description',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Área de conocimiento (RF05: toda propuesta tiene al menos un área)
     */
    public function area()
    {
        return $this->belongsTo(Area::class);
    }

}