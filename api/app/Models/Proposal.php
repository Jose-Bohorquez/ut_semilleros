<?php #archivo: backend/app/Models/Proposal.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

}