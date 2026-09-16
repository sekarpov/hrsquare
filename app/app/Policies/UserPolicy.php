<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->managesCandidates();
    }

    public function create(User $user): bool
    {
        return $this->viewAny($user);
    }

    public function update(User $user, User $target): bool
    {
        return $this->viewAny($user) && ($user->isAdmin() || ! $target->isAdmin());
    }

    public function delete(User $user, User $target): bool
    {
        return $this->viewAny($user) && ($user->isAdmin() || ! $target->isAdmin());
    }
}
