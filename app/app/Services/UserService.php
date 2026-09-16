<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class UserService
{
    public const DEFAULT_PASSWORD = 'QazWsx123456';

    public function save(array $data, ?User $user = null, ?User $actor = null): User
    {
        return DB::transaction(function () use ($data, $user, $actor) {
            $users = User::orderBy('id')->lockForUpdate()->get();
            if ($user) {
                $user = $users->firstWhere('id', $user->id);
                abort_unless($user, 404);
            }
            if ($actor) {
                $actor = $users->firstWhere('id', $actor->id);
                abort_unless($actor?->is_active, 403);
                Gate::forUser($actor)->authorize($user ? 'update' : 'create', $user ?? User::class);
                abort_if(($data['role'] ?? null) === UserRole::ADMIN->value && ! $actor->isAdmin(), 403);
            }
            if ($user) {
                $newRole = $data['role'] ?? $user->role->value;
                $active = $data['is_active'] ?? $user->is_active;
                $activeAdmins = $users->where('role', UserRole::ADMIN)->where('is_active', true)->count();
                if ($user->isAdmin() && $user->is_active && ($newRole !== UserRole::ADMIN->value || ! $active) && $activeAdmins <= 1) {
                    throw ValidationException::withMessages(['role' => ['Нельзя отключить или понизить роль последнего активного администратора.']]);
                }
                if ($user->role === UserRole::RECRUITER && $user->is_active && ($newRole !== UserRole::RECRUITER->value || ! $active) && $activeAdmins === 0 && ! ($newRole === UserRole::ADMIN->value && $active) && $users->where('role', UserRole::RECRUITER)->where('is_active', true)->count() <= 1) {
                    throw ValidationException::withMessages(['isActive' => ['Нельзя отключить последнего активного рекрутера без администратора.']]);
                }
            }
            if (! $user || isset($data['password'])) {
                $data['password'] = $data['password'] ?? self::DEFAULT_PASSWORD;
                $data['must_change_password'] = true;
                if ($user) {
                    $user->auth_version++;
                }
            }
            $user ??= new User;
            $user->fill($data);
            $user->save();

            return $user;
        });
    }

    public function delete(User $user, ?User $actor = null): void
    {
        DB::transaction(function () use ($user, $actor) {
            $users = User::orderBy('id')->lockForUpdate()->get();
            $user = $users->firstWhere('id', $user->id);
            abort_unless($user, 404);
            if ($actor) {
                $actor = $users->firstWhere('id', $actor->id);
                abort_unless($actor?->is_active, 403);
                Gate::forUser($actor)->authorize('delete', $user);
            }
            if ($user->isAdmin() && $user->is_active && $users->where('role', UserRole::ADMIN)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['user' => ['Нельзя удалить последнего активного администратора.']]);
            }
            if ($user->role === UserRole::RECRUITER && $user->is_active && $users->where('role', UserRole::ADMIN)->where('is_active', true)->isEmpty() && $users->where('role', UserRole::RECRUITER)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['user' => ['Нельзя удалить последнего активного рекрутера.']]);
            }
            if (DB::table('candidates')->where('created_by', $user->id)->exists() || DB::table('candidate_assessments')->where('evaluator_id', $user->id)->exists() || DB::table('candidate_hiring_managers')->where('user_id', $user->id)->exists() || DB::table('candidate_recruiters')->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['user' => ['Пользователь связан с кандидатами или оценками. Отключите учётную запись.']]);
            }
            $user->delete();
        });
    }
}
