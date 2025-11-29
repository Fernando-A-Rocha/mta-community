<?php

declare(strict_types=1);

namespace App\Http\Controllers;

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
        $resources = $user->resources()
            ->where('is_disabled', false)
            ->with(['currentVersion', 'displayImage'])
            ->withAvg('ratings', 'rating')
            ->withCount(['ratings', 'downloads'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('profile.show', [
            'user' => $user,
            'isOwner' => $isOwner,
            'resources' => $resources,
        ]);
    }
}
