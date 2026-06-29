<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use PHPOpenSourceSaver\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\Factories\HasFactory;
class Customer extends Authenticatable implements JWTSubject
{
    use HasFactory;

    protected $fillable = [
        'name',
        'phone_num',
        'email',
        'pending_email',
        'password',
        'status',
        'role',
        'verification_token',
        'verification_token_expires_at',
        'email_verified_at'
    ];

    protected $hidden = [
        'password',
        'verification_token'
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'verification_token_expires_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // relationships

    public function addresses():HasMany
    {
        return $this->hasMany(CustomerAddress::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function notifications():HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function payments():HasMany
    {
        return $this->hasMany(Payment::class);
    }

}
