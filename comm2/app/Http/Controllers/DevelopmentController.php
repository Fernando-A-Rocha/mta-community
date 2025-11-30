<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GitHubActivityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DevelopmentController extends Controller
{
    public function __construct(
        private readonly GitHubActivityService $githubActivityService
    ) {}

    /**
     * Display the development page.
     */
    public function index(): View
    {
        return view('development.index');
    }

    /**
     * Get GitHub activity for a specific repository.
     */
    public function activity(Request $request): JsonResponse
    {
        $repo = $request->string('repo')->toString(); // 'mtasa-blue' or 'mtasa-resources'
        $page = $request->integer('page', 1);
        $perPage = 10;

        if (! in_array($repo, ['mtasa-blue', 'mtasa-resources'], true)) {
            return response()->json(['error' => 'Invalid repository'], 400);
        }

        $allActivity = $this->githubActivityService->getActivity($repo);
        $fetchTimestamp = $this->githubActivityService->getFetchTimestamp($repo);

        // Paginate activity
        $offset = ($page - 1) * $perPage;
        $paginatedActivity = array_slice($allActivity, $offset, $perPage);
        $total = count($allActivity);

        // Format activity for JSON response
        $formattedActivity = array_map(function ($activity) {
            return [
                'type' => $activity['type'],
                'title' => $activity['title'],
                'author' => $activity['author'],
                'date' => $activity['date']->toIso8601String(),
                'date_human' => $activity['date']->diffForHumans(),
                'url' => $activity['url'],
            ];
        }, $paginatedActivity);

        return response()->json([
            'activity' => $formattedActivity,
            'pagination' => [
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'fetch_timestamp' => $fetchTimestamp,
        ]);
    }
}
