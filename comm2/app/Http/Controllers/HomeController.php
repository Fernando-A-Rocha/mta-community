<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MtaNewsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(
        private readonly MtaNewsService $mtaNewsService
    ) {}

    /**
     * Display the homepage.
     */
    public function index(): View
    {
        return view('home');
    }

    /**
     * Get MTA news entries.
     */
    public function news(Request $request): JsonResponse
    {
        $page = $request->integer('page', 1);
        $perPage = 5;

        $allNews = $this->mtaNewsService->getNews();
        $fetchTimestamp = $this->mtaNewsService->getFetchTimestamp();

        // Paginate news
        $offset = ($page - 1) * $perPage;
        $paginatedNews = array_slice($allNews, $offset, $perPage);
        $total = count($allNews);

        // Format news for JSON response
        $formattedNews = [];
        foreach ($paginatedNews as $index => $entry) {
            $formattedNews[] = [
                'url' => $entry['url'],
                'title' => $entry['title'],
                'author' => $entry['author'],
                'date' => $entry['date']->toIso8601String(),
                'date_formatted' => $entry['date']->format('F j, Y'),
                'is_first' => $page === 1 && $index === 0,
            ];
        }

        return response()->json([
            'news' => $formattedNews,
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
