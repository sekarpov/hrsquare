<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function save(array $data, ?User $user = null): User
    {
        return DB::transaction(function () use ($data, $user) {
            $users = User::orderBy('id')->lockForUpdate()->get();
            if ($user) {
                if (($data['role'] ?? $user->role->value) !== $user->role->value && (DB::table('candidate_hiring_managers')->where('user_id', $user->id)->exists() || DB::table('candidate_recruiters')->where('user_id', $user->id)->exists())) {
                    throw ValidationException::withMessages(['role' => ['Нельзя менять роль пользователя, связанного с кандидатами.']]);
                }
                $removingRecruiter = ($data['role'] !== UserRole::RECRUITER->value || ! $data['is_active']);
                if ($user->role === UserRole::RECRUITER && $user->is_active && $removingRecruiter && $users->where('role', UserRole::RECRUITER)->where('is_active', true)->count() <= 1) {
                    throw ValidationException::withMessages(['isActive' => ['Нельзя отключить последнего активного рекрутера.']]);
                }
            }
            $user ??= new User;
            $user->fill($data);
            $user->save();

            return $user;
        });
    }

    public function delete(User $user): void
    {
        DB::transaction(function () use ($user) {
            $users = User::orderBy('id')->lockForUpdate()->get();
            if ($user->role === UserRole::RECRUITER && $user->is_active && $users->where('role', UserRole::RECRUITER)->where('is_active', true)->count() <= 1) {
                throw ValidationException::withMessages(['user' => ['Нельзя удалить последнего активного рекрутера.']]);
            }
            if (DB::table('candidates')->where('created_by', $user->id)->exists() || DB::table('candidate_assessments')->where('evaluator_id', $user->id)->exists() || DB::table('candidate_hiring_managers')->where('user_id', $user->id)->exists() || DB::table('candidate_recruiters')->where('user_id', $user->id)->exists()) {
                throw ValidationException::withMessages(['user' => ['Пользователь связан с кандидатами или оценками. Отключите учётную запись.']]);
            }
            $user->delete();
        });
    }
}
