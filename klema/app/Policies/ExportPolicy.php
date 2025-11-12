<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

class ExportPolicy
{
    public function viewAny(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function view(User $user, Export $export): bool
    {
        if ($this->isAdmin($user)) {
            return true;
        }

        return $export->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $this->isAdmin($user);
    }

    public function download(User $user, Export $export): bool
    {
        return $this->view($user, $export);
    }

    private function isAdmin(User $user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $token = $user->currentAccessToken();

        return $token && ($token->can('*') || $token->can('role:admin'));
    }
}