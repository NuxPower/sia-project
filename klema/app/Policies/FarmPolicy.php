<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, Farm $farm): bool
    {
        return $user->id === $farm->user_id || $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, Farm $farm): bool
    {
        return $user->id === $farm->user_id || $user->isAdmin();
    }

    public function delete(User $user, Farm $farm): bool
    {
        return $user->id === $farm->user_id || $user->isAdmin();
    }
}
