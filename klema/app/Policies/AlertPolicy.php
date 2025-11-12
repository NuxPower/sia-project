<?php

namespace App\Policies;

use App\Models\Alert;
use App\Models\User;

class AlertPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->hasAlertAccess($user);
    }

    public function view(User $user, Alert $alert): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $alert->farm->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function update(User $user, Alert $alert): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $alert->farm->user_id === $user->id;
    }

    public function delete(User $user, Alert $alert): bool
    {
        return $this->update($user, $alert);
    }

    public function resolve(User $user, Alert $alert): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $alert->farm->user_id === $user->id;
    }

    private function isAdmin(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $token = $user->currentAccessToken();

        return $token && ($token->can('*') || $token->can('role:admin'));
    }

    private function hasAlertAccess(User $user): bool
    {
        if ($this->isAdmin($user) || $user->isFarmer()) {
            return true;
        }

        $token = $user->currentAccessToken();

        if (! $token) {
            return false;
        }

        return $token->can('*') || $token->can('alerts:read');
    }
}


