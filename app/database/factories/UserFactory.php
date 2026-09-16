<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Services\UserService;
use Illuminate\Database\Eloquent\Factories\Factory;

class UserFactory extends Factory
{
    public function admin(): static
    {
        return $this->state(fn () => ['role' => UserRole::ADMIN]);
    }

    public function temporaryPassword(): static
    {
        return $this->state(fn () => ['password' => UserService::DEFAULT_PASSWORD, 'must_change_password' => true]);
    }

    public function definition(): array
    {
        return ['full_name' => fake()->name(), 'login' => fake()->unique()->userName(), 'password' => 'password123', 'role' => UserRole::MANAGER, 'is_active' => true];
    }
}
