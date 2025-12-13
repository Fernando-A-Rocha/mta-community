<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MemberController extends Controller
{
    /**
     * Display the members page with top creators.
     */
    public function index(Request $request): View
    {
        // Get users who have uploaded resources or media, ranked by downloads, ratings, media, and reactions
        // Use subqueries for better performance
        $topCreators = User::query()
            ->where(function ($query) {
                $query->whereHas('resources', function ($q) {
                    $q->where('is_disabled', false);
                })->orWhereHas('media');
            })
            ->withCount([
                'resources' => function ($query) {
                    $query->where('is_disabled', false);
                },
                'media',
            ])
            ->with([
                'resources' => function ($query) {
                    $query->where('is_disabled', false)
                        ->withCount('downloads')
                        ->withAvg('ratings', 'rating');
                },
                'media' => function ($query) {
                    $query->withCount('reactions');
                },
            ])
            ->get()
            ->map(function ($user) {
                // Calculate total downloads across all resources
                $totalDownloads = $user->resources->sum(function ($resource) {
                    return $resource->downloads_count ?? 0;
                });

                // Calculate average rating across all resources
                // Only consider resources that have ratings
                $resourcesWithRatings = $user->resources->filter(function ($resource) {
                    return $resource->ratings_avg_rating !== null;
                });

                $averageRating = $resourcesWithRatings->isNotEmpty()
                    ? $resourcesWithRatings->avg('ratings_avg_rating')
                    : null;

                // Calculate media score: media count + total reactions received
                $mediaCount = $user->media_count ?? 0;
                $totalReactions = $user->media->sum(function ($media) {
                    return $media->reactions_count ?? 0;
                });
                $mediaScore = ($mediaCount * 1) + ($totalReactions * 0.5);

                // Calculate resource score (downloads * 0.7 + average_rating * 100 * 0.3)
                $resourceScore = ($totalDownloads * 0.7) + (($averageRating ?? 0) * 100 * 0.3);

                // Combined score: resource score + media score
                $score = $resourceScore + $mediaScore;

                return [
                    'user' => $user,
                    'total_downloads' => $totalDownloads,
                    'average_rating' => $averageRating ? round($averageRating, 2) : null,
                    'resources_count' => $user->resources_count,
                    'media_count' => $mediaCount,
                    'total_reactions' => $totalReactions,
                    'score' => $score,
                ];
            })
            ->sortByDesc('score')
            ->values()
            ->take(50); // Limit to top 50 creators

        return view('members.index', [
            'topCreators' => $topCreators,
        ]);
    }
}
