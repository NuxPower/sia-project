<?php

namespace App\Policies;

use App\Models\Farm;
use App\Models\User;

class FarmPolicy
{
    /**
     * Determine if the user can view any farms.
     */
    public function viewAny(User $user): bool
    {
        return true; // Users can list farms (filtered in controller by ownership)
    }

    /**
     * Determine if the user can view the farm.
     * Users can only see their own farms.
     */
    public function view(User $user, Farm $farm): bool
    {
        return $farm->user_id === $user->id;
    }

    /**
     * Determine if the user can create farms.
     */
    public function create(User $user): bool
    {
        return true; // Users can create farms
    }

    /**
     * Determine if the user can update the farm.
     * Users can only update their own farms.
     */
    public function update(User $user, Farm $farm): bool
    {
        return $farm->user_id === $user->id;
    }

    /**
     * Determine if the user can delete the farm.
     * Users can only delete their own farms.
     */
    public function delete(User $user, Farm $farm): bool
    {
        return $farm->user_id === $user->id;
    }
}
