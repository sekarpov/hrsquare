<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['full_name', 'login', 'password', 'role', 'is_active'];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return ['role' => UserRole::class, 'is_active' => 'boolean', 'password' => 'hashed'];
    }
}
