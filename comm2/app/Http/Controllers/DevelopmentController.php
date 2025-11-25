<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\GitHubActivityService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class DevelopmentController extends Controller
{
    public function __construct(
        private readonly GitHubActivityService $githubActivityService
    ) {}

    /**
     * Display the development page with GitHub activity.
     */
    public function index(): View
    {
        $allMtasaBlueActivity = $this->githubActivityService->getActivity('mtasa-blue');
        $allMtasaResourcesActivity = $this->githubActivityService->getActivity('mtasa-resources');

        // Paginate each repository's activity (10 per page)
        $perPage = 10;

        // Paginate mtasa-blue activity
        $currentPageBlue = Paginator::resolveCurrentPage('page_blue');
        $offsetBlue = ($currentPageBlue - 1) * $perPage;
        $paginatedBlue = array_slice($allMtasaBlueActivity, $offsetBlue, $perPage);
        $mtasaBluePaginator = new LengthAwarePaginator(
            $paginatedBlue,
            count($allMtasaBlueActivity),
            $perPage,
            $currentPageBlue,
            [
                'path' => request()->url(),
                'pageName' => 'page_blue',
            ]
        );

        // Paginate mtasa-resources activity
        $currentPageResources = Paginator::resolveCurrentPage('page_resources');
        $offsetResources = ($currentPageResources - 1) * $perPage;
        $paginatedResources = array_slice($allMtasaResourcesActivity, $offsetResources, $perPage);
        $mtasaResourcesPaginator = new LengthAwarePaginator(
            $paginatedResources,
            count($allMtasaResourcesActivity),
            $perPage,
            $currentPageResources,
            [
                'path' => request()->url(),
                'pageName' => 'page_resources',
            ]
        );

        $mtasaBlueFetchTimestamp = $this->githubActivityService->getFetchTimestamp('mtasa-blue');
        $mtasaResourcesFetchTimestamp = $this->githubActivityService->getFetchTimestamp('mtasa-resources');

        return view('development.index', [
            'mtasaBlueActivity' => $mtasaBluePaginator,
            'mtasaResourcesActivity' => $mtasaResourcesPaginator,
            'mtasaBlueFetchTimestamp' => $mtasaBlueFetchTimestamp,
            'mtasaResourcesFetchTimestamp' => $mtasaResourcesFetchTimestamp,
        ]);
    }
}
