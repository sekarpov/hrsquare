<?php

namespace App\Models;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use HasFactory;

    protected $fillable = ['full_name', 'login', 'password', 'role', 'is_active', 'must_change_password'];

    protected $attributes = ['must_change_password' => false, 'auth_version' => 0];

    protected $hidden = ['password', 'auth_version'];

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function managesCandidates(): bool
    {
        return in_array($this->role, [UserRole::ADMIN, UserRole::RECRUITER], true);
    }

    protected function casts(): array
    {
        return ['role' => UserRole::class, 'is_active' => 'boolean', 'must_change_password' => 'boolean', 'auth_version' => 'integer', 'password' => 'hashed'];
    }
}
