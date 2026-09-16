<?php

use App\Enums\UserRole;
use App\Models\User;
use App\Services\UserService;
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

Artisan::command('hrsquare:make-admin {login}', function () {
    $user = User::where('login', $this->argument('login'))->first();
    if (! $user || ! $user->is_active) {
        $this->error('Активный пользователь с таким логином не найден.');

        return 1;
    }
    app(UserService::class)->save(['role' => UserRole::ADMIN->value], $user);
    $this->info('Пользователю назначена роль администратора. Пароль и связи с кандидатами сохранены.');
})->purpose('Promote an existing active account to administrator');
