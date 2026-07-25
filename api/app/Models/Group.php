<?php // archivo: backend/app/Models/Group.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Group extends Model
{

    protected $fillable = [

        'name',
        'code',
        'status'

    ];

}