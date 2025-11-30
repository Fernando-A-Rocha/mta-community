<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        $profileVisibility = $user->profile_visibility ?? 'public';
        $isPublic = $profileVisibility === 'public';

        // If not owner and not public, abort with 403
        if (! $isOwner && ! $isPublic) {
            abort(403, 'This profile is private.');
        }

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

        return view('profile.show', [
            'user' => $user,
            'isOwner' => $isOwner,
            'resources' => $resources,
            'profileIsPublic' => $isPublic,
            'viewerReport' => $viewerReport,
        ]);
    }
}
