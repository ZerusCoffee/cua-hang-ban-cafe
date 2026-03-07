<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as CustomerAuthenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends CustomerAuthenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'avatar', 'phone',
        'password', 'email_verified_at', 'is_locked',
    ];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'password'          => 'hashed',
            'email_verified_at' => 'datetime',
            'is_locked'         => 'boolean',
        ];
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function defaultAddress()
    {
        return $this->hasOne(Address::class)->where('is_default', true);
    }
}
