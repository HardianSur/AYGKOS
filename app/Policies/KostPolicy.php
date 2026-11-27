<?php

namespace App\Policies;

use App\Models\User;

class KostPolicy
{
    /**
     * Determine whether the user may see their own kost list.
     */
    public function viewAny(User $user): bool
    {
        return $this->canManageKosts($user);
    }

    /**
     * Determine whether the user may submit a new kost.
     */
    public function create(User $user): bool
    {
        return $this->canManageKosts($user);
    }

    /**
     * A kost owner must be active and have verified their identity.
     */
    private function canManageKosts(User $user): bool
    {
        return $user->is_active
            && $user->isPemilikKost()
            && $user->hasVerifiedIdentity();
    }
}
