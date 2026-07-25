<?php # archivo: backend/app/models/cat.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Cat extends Model
{

    protected $fillable = [

        'name',
        'code',
        'address',
        'city',
        'phone1',
        'phone2',
        'phone3',
        'status'

    ];

}