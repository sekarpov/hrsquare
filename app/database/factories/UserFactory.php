<?php

namespace Database\Factories;

use App\Enums\UserRole;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function definition(): array
    {
        return ['full_name' => fake()->name(), 'login' => fake()->unique()->userName(), 'password' => 'password123', 'role' => UserRole::MANAGER, 'is_active' => true];
    }
}
