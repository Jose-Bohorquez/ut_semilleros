<?php # archivo: backend/app/Models/Result.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Result extends Model
{

    protected $fillable = [
        'seedbed_id',
        'content',
        'status'
    ];

    public function seedbed()
    {
        return $this->belongsTo(Seedbed::class);
    }

}