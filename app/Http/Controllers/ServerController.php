<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\MtaServerService;
use App\Services\MtaStatsService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServerController extends Controller
{
    public function __construct(
        private readonly MtaServerService $mtaServerService,
        private readonly MtaStatsService $mtaStatsService,
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
            'pagination' => [
                'current_page' => $page,
                'last_page' => (int) ceil($total / $perPage),
                'per_page' => $perPage,
                'total' => $total,
            ],
            'fetch_timestamp' => $fetchTimestamp,
        ]);
    }

    /**
     * Get historical player/server counts.
     */
    public function history(Request $request): JsonResponse
    {
        $perPageDays = 30;
        $page = max(1, $request->integer('page', 1));
        $daysToFetch = min($perPageDays * $page, 365);

        $history = $this->mtaStatsService->getHistory($daysToFetch);

        $now = CarbonImmutable::now();
        $windowStart = $now->subDays($perPageDays * $page);
        $windowEnd = $now->subDays($perPageDays * ($page - 1));

        $windowData = [];
        foreach ($history as $row) {
            try {
                $timestamp = CarbonImmutable::parse($row['created_at']);
            } catch (\Throwable) {
                continue;
            }

            if ($timestamp->betweenIncluded($windowStart, $windowEnd)) {
                $windowData[] = [
                    'players' => (int) ($row['players'] ?? 0),
                    'servers' => (int) ($row['servers'] ?? 0),
                    'created_at' => $timestamp->toIso8601String(),
                ];
            }
        }

        usort($windowData, static fn (array $a, array $b) => strcmp($a['created_at'], $b['created_at']));

        $hasNext = false;
        foreach ($history as $row) {
            try {
                $timestamp = CarbonImmutable::parse($row['created_at']);
            } catch (\Throwable) {
                continue;
            }

            if ($timestamp->lt($windowStart)) {
                $hasNext = true;
                break;
            }
        }

        return response()->json([
            'data' => $windowData,
            'meta' => [
                'page' => $page,
                'per_page_days' => $perPageDays,
                'has_prev' => $page > 1,
                'has_next' => $hasNext,
                'range' => [
                    'from' => $windowStart->toIso8601String(),
                    'to' => $windowEnd->toIso8601String(),
                ],
            ],
        ]);
    }
}
