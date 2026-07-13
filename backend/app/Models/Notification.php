<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    protected $fillable = [
        'customer_id',
        'event_type',
        'channel',
        'title',
        'message',
        'status',
        'is_read',
        'read_at',
        'scheduled_at',
        'sent_status',
        'sent_at'
    ];

    protected $casts = [
        'is_read' => 'boolean',
        'read_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function customer():BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
