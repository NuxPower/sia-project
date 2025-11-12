<?php

namespace App\Policies;

use App\Models\Activity;
use App\Models\User;

class ActivityPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return $this->hasAbility($user, 'activities:read');
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Activity $activity): bool
    {
        return $this->ownsActivity($user, $activity) || $user->isAdmin();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $this->hasAbility($user, 'activities:write');
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Activity $activity): bool
    {
        return $this->ownsActivity($user, $activity) || $user->isAdmin();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Activity $activity): bool
    {
        return $this->ownsActivity($user, $activity) || $user->isAdmin();
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Activity $activity): bool
    {
        return $this->ownsActivity($user, $activity) || $user->isAdmin();
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Activity $activity): bool
    {
        return $user->isAdmin();
    }

    private function ownsActivity(User $user, Activity $activity): bool
    {
        return $activity->user_id === $user->id;
    }

    private function hasAbility(User $user, string $ability): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        $token = $user->currentAccessToken();

        if (! $token) {
            return $user->isFarmer();
        }

        return $token->can('*') || $token->can($ability) || $token->can('role:farmer');
    }
}
