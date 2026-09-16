<?php

namespace App\Policies;

use App\Enums\UserRole;
use App\Models\Candidate;
use App\Models\User;

class CandidatePolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Candidate $candidate): bool
    {
        return $user->role === UserRole::RECRUITER || $candidate->hiringManagers()->where('users.id', $user->id)->exists();
    }

    public function create(User $user): bool
    {
        return in_array($user->role, [UserRole::RECRUITER, UserRole::MANAGER], true);
    }

    public function update(User $user, Candidate $candidate): bool
    {
        return $user->role === UserRole::RECRUITER;
    }

    public function delete(User $user, Candidate $candidate): bool
    {
        return $user->role === UserRole::RECRUITER;
    }
}
