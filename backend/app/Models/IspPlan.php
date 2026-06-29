<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IspPlan extends Model
{
    protected $fillable = [
        'name',
        'description',
        'price',
        'status',
        'upload_speed',
        'download_speed'
    ];

    public function subscriptions():HasMany
    {
        return $this->hasMany(Subscription::class);
    }
}
