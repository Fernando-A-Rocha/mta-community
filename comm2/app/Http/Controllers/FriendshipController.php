<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Enums\NotificationCategory;
use App\Models\Friendship;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades.Auth;

class FriendshipController extends Controller
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function store(User $user): RedirectResponse
    {
        $requester = Auth::user();

        abort_if($requester->id === $user->id, 403);

        if (! $user->allow_friend_requests) {
            return back()->withErrors(['friend' => __('This user is not accepting friend requests right now.')]);
        }

        $friendship = Friendship::between($requester->id, $user->id)->first();

        if ($friendship) {
            if ($friendship->status === FriendshipStatus::Accepted) {
                return back()->with('success', __('You are already friends with :user.', ['user' => $user->name]));
            }

            if ($friendship->status === FriendshipStatus::Pending) {
                if ($friendship->addressee_id === $requester->id) {
                    return $this->accept($user);
                }

                return back()->with('success', __('Friend request already sent.'));
            }

            if ($friendship->status === FriendshipStatus::Declined) {
                $friendship->update([
                    'requester_id' => $requester->id,
                    'addressee_id' => $user->id,
                    'status' => FriendshipStatus::Pending,
                    'responded_at' => null,
                ]);
            }
        } else {
            $friendship = Friendship::create([
                'requester_id' => $requester->id,
                'addressee_id' => $user->id,
                'status' => FriendshipStatus::Pending,
            ]);
        }

        $this->notifications->notify(
            $user,
            NotificationCategory::Friends,
            __('New friend request from :user', ['user' => $requester->name]),
            __(':user would like to connect with you.', ['user' => $requester->name]),
            [
                'friendship_id' => $friendship->id,
                'requester_id' => $requester->id,
            ],
            route('profile.show', $requester)
        );

        return back()->with('success', __('Friend request sent.'));
    }

    public function storeByUsername(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255'],
        ]);

        $target = User::where('name', $validated['username'])->first();

        if (! $target) {
            return back()->withErrors(['username' => __('User ":name" was not found.', ['name' => $validated['username']])]);
        }

        return $this->store($target);
    }

    public function accept(User $user): RedirectResponse
    {
        $viewer = Auth::user();

        $friendship = Friendship::between($viewer->id, $user->id)
            ->where('status', FriendshipStatus::Pending->value)
            ->where('addressee_id', $viewer->id)
            ->firstOrFail();

        $friendship->update([
            'status' => FriendshipStatus::Accepted,
            'responded_at' => now(),
        ]);

        $this->notifications->notify(
            $user,
            NotificationCategory::Friends,
            __(':user accepted your friend request', ['user' => $viewer->name]),
            __('You are now friends with :user.', ['user' => $viewer->name]),
            [
                'friendship_id' => $friendship->id,
                'accepted_by' => $viewer->id,
            ],
            route('profile.show', $viewer)
        );

        return back()->with('success', __('Friend request accepted.'));
    }

    public function destroy(User $user): RedirectResponse
    {
        $viewer = Auth::user();

        $friendship = Friendship::between($viewer->id, $user->id)->first();

        if (! $friendship) {
            return back();
        }

        $message = __('Friend removed.');

        if ($friendship->status === FriendshipStatus::Pending) {
            if ($friendship->addressee_id === $viewer->id) {
                $friendship->update([
                    'status' => FriendshipStatus::Declined,
                    'responded_at' => now(),
                ]);

                $this->notifications->notify(
                    $user,
                    NotificationCategory::Friends,
                    __(':user declined your friend request', ['user' => $viewer->name]),
                    __('No worries, you can always connect later.'),
                    [
                        'friendship_id' => $friendship->id,
                        'declined_by' => $viewer->id,
                    ]
                );

                $message = __('Friend request declined.');
            } else {
                $friendship->delete();
                $message = __('Friend request cancelled.');
            }
        } else {
            $friendship->delete();

            $this->notifications->notify(
                $user,
                NotificationCategory::Friends,
                __(':user removed you from their friends list', ['user' => $viewer->name]),
                __('You can re-send a request anytime.'),
                [
                    'removed_by' => $viewer->id,
                ]
            );
        }

        return back()->with('success', $message);
    }
}
