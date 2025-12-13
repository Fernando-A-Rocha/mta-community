<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Media;
use App\Models\User;

class MediaPolicy
{
    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Media $media): bool
    {
        // User can delete their own media, or moderators+ can delete any
        return $user->id === $media->user_id || $user->isModerator();
    }
}
