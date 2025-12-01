<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\FriendService;
use Illuminate\Http\RedirectResponse;

class FriendController extends Controller
{
    public function store(User $user, FriendService $friendService): RedirectResponse
    {
        $authUser = auth()->user();

        abort_if(! $authUser, 403);

        if ($authUser->is($user)) {
            return back()->with('friends_error', __('You cannot add yourself as a friend.'));
        }

        if ($authUser->hasFriend($user)) {
            return back()->with('friends_info', __(':name is already in your friends list.', ['name' => $user->name]));
        }

        $friendService->add($authUser, $user);

        return back()->with('friends_success', __('You added :name to your friends.', ['name' => $user->name]));
    }

    public function destroy(User $user, FriendService $friendService): RedirectResponse
    {
        $authUser = auth()->user();

        abort_if(! $authUser, 403);

        if ($authUser->is($user)) {
            return back();
        }

        $friendService->remove($authUser, $user);

        return back()->with('friends_success', __('You removed :name from your friends.', ['name' => $user->name]));
    }
}

