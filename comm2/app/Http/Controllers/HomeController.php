<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MtaNewsService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly MtaNewsService $mtaNewsService
    ) {}

    /**
     * Display the homepage with news entries.
     */
    public function index(): View
    {
        $allNews = $this->mtaNewsService->getNews();

        $perPage = 5;
        $currentPage = Paginator::resolveCurrentPage('page');
        $offset = ($currentPage - 1) * $perPage;
        $paginatedNews = array_slice($allNews, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $paginatedNews,
            count($allNews),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        $fetchTimestamp = $this->mtaNewsService->getFetchTimestamp();

        return view('home', [
            'news' => $paginator,
            'fetchTimestamp' => $fetchTimestamp,
        ]);
    }
}
