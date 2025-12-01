<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;

class FriendService
{
    /**
     * Add a friend for the authenticated user (bi-directional)
     */
    public function add(User $user, User $friend): void
    {
        if ($user->is($friend)) {
            return;
        }

        if ($user->hasFriend($friend)) {
            return;
        }

        DB::transaction(function () use ($user, $friend) {
            $user->friends()->syncWithoutDetaching([$friend->id]);
            $friend->friends()->syncWithoutDetaching([$user->id]);
        });
    }

    /**
     * Remove a friend relationship (bi-directional)
     */
    public function remove(User $user, User $friend): void
    {
        DB::transaction(function () use ($user, $friend) {
            $user->friends()->detach($friend->id);
            $friend->friends()->detach($user->id);
        });
    }
}

