<?php

namespace App\Policies;

use App\Models\Listing;
use App\Models\User;

class ListingPolicy
{
    public function view(?User $user, Listing $listing): bool
    {
        if (in_array($listing->status, ['active', 'reserved', 'sold'], true)) {
            return true;
        }

        return $user !== null && ($user->id === $listing->user_id || $user->isAdmin());
    }

    public function update(User $user, Listing $listing): bool
    {
        return $user->id === $listing->user_id || $user->isAdmin();
    }

    public function delete(User $user, Listing $listing): bool
    {
        return $this->update($user, $listing);
    }
}
