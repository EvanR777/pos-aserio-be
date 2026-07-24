<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $table = 'users';
    protected $guarded = [];
    protected $hidden = ['password', 'legacy_user', 'remember_token'];

    protected function casts(): array
    {
        return ['password' => 'hashed'];
    }
}
