<?php

namespace App\Policies;

use App\Models\Item;
use App\Models\User;

class ItemPolicy
{
    /**
     * Determine if the given item can be updated by the user.
     */
    public function update(User $user, Item $item): bool
    {
        return is_null($item->user_id) || $item->user_id === $user->id;
    }

    /**
     * Determine if the given item can be deleted by the user.
     */
    public function delete(User $user, Item $item): bool
    {
        return is_null($item->user_id) || $item->user_id === $user->id;
    }
}
