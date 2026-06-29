<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class IspPlan extends Model
{
    use HasFactory;

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
