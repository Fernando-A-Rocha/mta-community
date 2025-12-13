<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class UserFollowController extends Controller
{
    public function store(User $user): RedirectResponse
    {
        $follower = Auth::user();

        abort_if($follower->id === $user->id, 403);

        if (($user->profile_visibility ?? 'public') !== 'public') {
            return back()->withErrors(['follow' => __('This profile is private and cannot be followed.')]);
        }

        $follower->followedUsers()->syncWithoutDetaching([$user->id]);

        return back()->with('success', __('You are now following :user.', ['user' => $user->name]));
    }

    public function destroy(User $user): RedirectResponse
    {
        $follower = Auth::user();

        $follower->followedUsers()->detach($user->id);

        return back()->with('success', __('You are no longer following :user.', ['user' => $user->name]));
    }
}
