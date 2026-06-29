<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'customer_id',
        'type',
        'title',
        'message',
        'status',
        'scheduled_at',
        'send_status',
        'sent_at'
    ];

    public function customer():BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
