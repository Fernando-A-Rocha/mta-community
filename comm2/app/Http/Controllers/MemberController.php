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
        // Get users who have uploaded resources, ranked by downloads and ratings
        // Use subqueries for better performance
        $topCreators = User::query()
            ->whereHas('resources', function ($query) {
                $query->where('is_disabled', false);
            })
            ->withCount([
                'resources' => function ($query) {
                    $query->where('is_disabled', false);
                },
            ])
            ->with([
                'resources' => function ($query) {
                    $query->where('is_disabled', false)
                        ->withCount('downloads')
                        ->withAvg('ratings', 'rating');
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

                // Calculate a combined score (downloads * 0.7 + average_rating * 100 * 0.3)
                // This gives more weight to downloads but still considers ratings
                $score = ($totalDownloads * 0.7) + (($averageRating ?? 0) * 100 * 0.3);

                return [
                    'user' => $user,
                    'total_downloads' => $totalDownloads,
                    'average_rating' => $averageRating ? round($averageRating, 2) : null,
                    'resources_count' => $user->resources_count,
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
