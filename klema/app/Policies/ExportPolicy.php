<?php

namespace App\Policies;

use App\Models\Export;
use App\Models\User;

class ExportPolicy
{
    /**
     * Determine whether the user can view/download the export.
     */
    public function view(User $user, Export $export): bool
    {
        return (int) $export->user_id === (int) $user->getKey();
    }

    /**
     * Alias for download checks.
     */
    public function download(User $user, Export $export): bool
    {
        return $this->view($user, $export);
    }
}