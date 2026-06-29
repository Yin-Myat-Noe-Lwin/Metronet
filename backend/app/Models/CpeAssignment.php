<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CpeAssignment extends Model
{
    protected $fillable = [
        'cpe_id',
        'subscription_id',
        'assigned_at',
        'status'
    ];

    public function cpe():BelongsTo
    {
        return $this->belongsTo(Cpe::class);
    }

}
