<?php #archivo: backend/app/Models/Audit.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Audit extends Model
{

    protected $fillable = [
        'user_id',
        'action',
        'table_name',
        'record_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}