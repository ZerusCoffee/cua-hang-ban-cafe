<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as CustomerAuthenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Customer extends CustomerAuthenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        "name",
        "email",
        "avatar",
        "phone",
        "password",
        "email_verified_at",
        "is_locked",
    ];

    protected $hidden = ["password", "remember_token"];

    protected function casts(): array
    {
        return [
            "password" => "hashed",
            "email_verified_at" => "datetime",
            "is_locked" => "boolean",
        ];
    }
}
