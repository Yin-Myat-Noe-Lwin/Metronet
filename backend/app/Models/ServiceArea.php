<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceArea extends Model
{
    protected $fillable = [
        'region',
        'city',
        'township',
        'status'
    ];
}
