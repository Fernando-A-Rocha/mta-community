<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MtaServerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly MtaServerService $mtaServerService
    ) {}

    /**
     * Display the list of MTA servers.
     */
    public function index(): View
    {
        return view('servers.index');
    }

    /**
     * Get MTA servers list.
     */
    public function servers(Request $request): JsonResponse
    {
        $allServers = $this->mtaServerService->getServers();
        $statistics = $this->mtaServerService->getStatistics();

        // Create a map of server positions in the original list (using IP:Port as unique identifier)
        $serverPositions = [];
        foreach ($allServers as $index => $server) {
            $key = $server['ip'].':'.$server['port'];
            $serverPositions[$key] = $index + 1; // Position starts at 1
        }

        // Get search query from request
        $searchQuery = $request->string('search')->toString();

        // Filter servers by search query if provided
        $servers = $allServers;
        if (! empty($searchQuery)) {
            $searchQuery = strtolower(trim($searchQuery));
            $servers = array_filter($servers, function ($server) use ($searchQuery) {
                $name = strtolower($server['name'] ?? '');
                $ip = $server['ip'] ?? '';
                $port = (string) ($server['port'] ?? '');
                $ipPort = ($ip.':'.$port);

                return str_contains($name, $searchQuery) || str_contains($ipPort, $searchQuery);
            });
            $servers = array_values($servers); // Re-index array
        }

        // Add original position to each server
        $servers = array_map(function ($server) use ($serverPositions) {
            $key = $server['ip'].':'.$server['port'];
            $server['original_position'] = $serverPositions[$key] ?? null;

            return $server;
        }, $servers);

        $perPage = 30;
        $page = $request->integer('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedServers = array_slice($servers, $offset, $perPage);
        $total = count($servers);

        // Format servers for JSON response
        $formattedServers = array_map(function ($server, $index) use ($page, $perPage) {
            $position = $server['original_position'] ?? (($page - 1) * $perPage + $index + 1);

            return [
                'name' => $server['name'],
                'ip' => $server['ip'],
                'port' => $server['port'],
                'players' => $server['players'],
                'maxplayers' => $server['maxplayers'],
                'password' => $server['password'],
                'version' => $server['version'] ?? 'N/A',
                'original_position' => $position,
            ];
        }, $paginatedServers, array_keys($paginatedServers));

        $fetchTimestamp = $this->mtaServerService->getFetchTimestamp();

        return response()->json([
            'servers' => $formattedServers,
            'statistics' => $statistics,
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
