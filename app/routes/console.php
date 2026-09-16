<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Artisan;

Artisan::command('hrsquare:create-recruiter {login} {fullName}', function () {
    $password = $this->secret('Пароль (минимум 8 символов)');
    if (! $password || strlen($password) < 8) {
        $this->error('Пароль слишком короткий.');

        return 1;
    }
    if (! preg_match('/^[a-zA-Z0-9_.-]+$/', $this->argument('login'))) {
        $this->error('Недопустимый логин.');

        return 1;
    }
    if (User::where('login', $this->argument('login'))->exists()) {
        $this->error('Логин уже занят.');

        return 1;
    }
    User::create(['login' => $this->argument('login'), 'full_name' => $this->argument('fullName'), 'password' => $password, 'role' => UserRole::RECRUITER, 'is_active' => true]);
    $this->info('Рекрутер создан.');
})->purpose('Create the initial production recruiter interactively');
