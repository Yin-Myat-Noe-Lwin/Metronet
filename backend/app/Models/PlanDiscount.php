<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlanDiscount extends Model
{
    protected $table = 'plan_discounts';

    protected $fillable = [
        'plan_id',
        'duration_months',
        'discount_percentage',
        'is_active'
    ];

    protected $casts = [
        'discount_percentage' => 'decimal:2',
        'is_active' => 'boolean',
        'duration_months' => 'integer'
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(IspPlan::class);
    }
}
