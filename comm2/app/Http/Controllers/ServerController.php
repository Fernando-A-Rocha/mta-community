<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MtaServerService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly MtaServerService $mtaServerService
    ) {
    }

    /**
     * Display the list of MTA servers.
     */
    public function index(): View
    {
        $allServers = $this->mtaServerService->getServers();
        $statistics = $this->mtaServerService->getStatistics();

        // Create a map of server positions in the original list (using IP:Port as unique identifier)
        $serverPositions = [];
        foreach ($allServers as $index => $server) {
            $key = $server['ip'] . ':' . $server['port'];
            $serverPositions[$key] = $index + 1; // Position starts at 1
        }

        // Get search query from request
        $searchQuery = request()->query('search', '');

        // Filter servers by search query if provided
        $servers = $allServers;
        if (!empty($searchQuery)) {
            $searchQuery = strtolower(trim($searchQuery));
            $servers = array_filter($servers, function ($server) use ($searchQuery) {
                $name = strtolower($server['name'] ?? '');
                $ip = $server['ip'] ?? '';
                $port = (string) ($server['port'] ?? '');
                $ipPort = ($ip . ':' . $port);

                return str_contains($name, $searchQuery) || str_contains($ipPort, $searchQuery);
            });
            $servers = array_values($servers); // Re-index array
        }

        // Add original position to each server
        $servers = array_map(function ($server) use ($serverPositions) {
            $key = $server['ip'] . ':' . $server['port'];
            $server['original_position'] = $serverPositions[$key] ?? null;
            return $server;
        }, $servers);

        $perPage = 30;
        $currentPage = Paginator::resolveCurrentPage('page');
        $offset = ($currentPage - 1) * $perPage;
        $paginatedServers = array_slice($servers, $offset, $perPage);

        $paginator = new LengthAwarePaginator(
            $paginatedServers,
            count($servers),
            $perPage,
            $currentPage,
            [
                'path' => request()->url(),
                'pageName' => 'page',
            ]
        );

        // Append search query to pagination links
        if (!empty($searchQuery)) {
            $paginator->appends(['search' => $searchQuery]);
        }

        return view('servers.index', [
            'servers' => $paginator,
            'statistics' => $statistics,
            'searchQuery' => $searchQuery,
        ]);
    }
}

