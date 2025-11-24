<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Resource;
use App\Models\User;

class ResourcePolicy
{
    /**
     * Determine whether the user can update the resource.
     */
    public function update(User $user, Resource $resource): bool
    {
        return $user->id === $resource->user_id || $user->isModerator();
    }

    /**
     * Determine whether the user can disable the resource.
     */
    public function disable(User $user, Resource $resource): bool
    {
        return $user->isModerator();
    }

    /**
     * Determine whether the user can delete the resource.
     */
    public function delete(User $user, Resource $resource): bool
    {
        return $user->id === $resource->user_id || $user->isModerator();
    }

    /**
     * Determine whether the user can delete a version of the resource.
     */
    public function deleteVersion(User $user, Resource $resource): bool
    {
        return $user->id === $resource->user_id || $user->isModerator();
    }
}
