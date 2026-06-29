<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cpe extends Model
{
    use HasFactory;

    protected $fillable = [
        'serial_number',
        'mac_address',
        'status'
    ];

    public function assignments():HasMany
    {
        return $this->hasMany(CpeAssignment::class);
    }
}
