<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\FriendshipStatus;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile page.
     */
    public function show(Request $request, User $user): View
    {
        // Check if profile is visible
        $isOwner = auth()->check() && auth()->id() === $user->id;
        $isModerator = auth()->check() && auth()->user()->isModerator();
        $profileVisibility = $user->profile_visibility ?? 'public';
        $isPublic = $profileVisibility === 'public';

        // If not owner, not public, and not moderator, abort with 403
        if (! $isOwner && ! $isPublic && ! $isModerator) {
            abort(403, 'This profile is private.');
        }

        $user->loadCount('followers');

        // Load user's resources with ratings and downloads
        $resourcesQuery = $user->resources()
            ->with(['currentVersion', 'displayImage'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'downloads']);

        // Only show disabled resources to moderators+
        if (! auth()->check() || ! auth()->user()->isModerator()) {
            $resourcesQuery->where('is_disabled', false);
        }

        $resources = $resourcesQuery
            ->orderBy('created_at', 'desc')
            ->get();

        $viewerReport = null;
        if (auth()->check() && auth()->id() !== $user->id) {
            $viewerReport = Report::query()
                ->where('reporter_id', auth()->id())
                ->where('reportable_type', Report::TYPE_USER)
                ->where('reportable_id', $user->id)
                ->latest('id')
                ->first();
        }

        $viewer = auth()->user();
        $friendship = $viewer ? $viewer->friendshipWith($user) : null;
        $isFriend = $friendship?->status === FriendshipStatus::Accepted;
        $incomingFriendRequest = $friendship?->status === FriendshipStatus::Pending && $friendship?->addressee_id === $viewer?->id;
        $outgoingFriendRequest = $friendship?->status === FriendshipStatus::Pending && $friendship?->requester_id === $viewer?->id;
        $isFollowingUser = $viewer ? $viewer->isFollowingUser($user) : false;

        return view('profile.show', [
            'user' => $user,
            'isOwner' => $isOwner,
            'isModerator' => $isModerator,
            'resources' => $resources,
            'profileIsPublic' => $isPublic,
            'viewerReport' => $viewerReport,
            'friendship' => $friendship,
            'isFriend' => $isFriend,
            'incomingFriendRequest' => $incomingFriendRequest,
            'outgoingFriendRequest' => $outgoingFriendRequest,
            'isFollowingUser' => $isFollowingUser,
        ]);
    }
}
