<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Subscription extends Model
{
    protected $fillable = [
        'customer_id',
        'plan_id',
        'status',
        'start_date',
        'end_date',
        'duration_months',
        'auto_renew'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function plan() :BelongsTo
    {
        return $this->belongsTo(IspPlan::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function cpeAssignments(): HasMany
    {
        return $this->hasMany(CpeAssignment::class);
    }
}
