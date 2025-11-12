<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    public function viewAny(User $user): bool
    {
        if ($user->isAdmin() || $user->isFarmer()) {
            return true;
        }

        $token = $user->currentAccessToken();

        if (! $token) {
            return false;
        }

        return $token->can('*') || $token->can('farms:read');
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
