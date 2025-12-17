<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtaServerService
{
    private const CACHE_KEY = 'mta_servers_list';

    private const CACHE_TIMESTAMP_KEY = 'mta_servers_list_timestamp';

    /**
     * Get filtered and cached MTA servers.
     *
     * @return array<int, array{name: string, ip: string, port: int, players: int, maxplayers: int, password: int, version: string}>
     */
    public function getServers(): array
    {
        $cacheKey = self::CACHE_KEY;
        $timestampKey = self::CACHE_TIMESTAMP_KEY;
        $cacheDuration = (int) config('mta.cache_duration', 300);
        $targetVersion = config('mta.current_stable_version', '1.6');

        // Check if we need to refresh the cache
        $lastFetch = Cache::get($timestampKey);
        $needsRefresh = $lastFetch === null || (time() - $lastFetch) > $cacheDuration;
        // $needsRefresh = true; // Bypass cache for testing
        if ($needsRefresh) {
            $servers = $this->fetchAndFilterServers($targetVersion);
            // Only cache if we have servers, so we can retry immediately if we get 0 servers
            if (count($servers) > 0) {
                Cache::put($cacheKey, $servers, now()->addHours(24)); // Store for 24 hours as backup
                Cache::put($timestampKey, time(), now()->addHours(24));
            }
        } else {
            $servers = Cache::get($cacheKey, []);
        }

        return $servers;
    }

    /**
     * Fetch servers from API and filter them.
     *
     * @return array<int, array{name: string, ip: string, port: int, players: int, maxplayers: int, password: int, version: string}>
     */
    private function fetchAndFilterServers(string $targetVersion): array
    {
        $apiUrl = config('mta.servers_api_url', 'https://multitheftauto.com/api/');
        $verifySsl = (bool) config('mta.verify_ssl', false);

        try {
            $httpClient = Http::timeout(10);
            if (! $verifySsl) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->get($apiUrl);
            $rawBody = $response->body();

            if (! $response->successful()) {
                Log::warning('Failed to fetch MTA servers', [
                    'status' => $response->status(),
                    'url' => $apiUrl,
                ]);

                // Return cached data if available, otherwise empty array
                return Cache::get(self::CACHE_KEY, []);
            }

            $allServers = json_decode($rawBody, true);

            if (! is_array($allServers)) {
                Log::warning('Invalid response from MTA API', [
                    'json_error' => json_last_error_msg(),
                    'response_sample' => mb_substr($rawBody, 0, 500, 'UTF-8'),
                ]);

                return Cache::get(self::CACHE_KEY, []);
            }

            // Filter servers: must have players > 0, version matches, and not passworded
            $filteredServers = array_filter($allServers, function ($server) use ($targetVersion) {
                if (! is_array($server)) {
                    return false;
                }

                $players = (int) ($server['players'] ?? 0);
                $version = (string) ($server['version'] ?? '');
                $password = (int) ($server['password'] ?? 0);

                return $players > 0 && $version === $targetVersion && $password === 0;
            });

            // Sort by player count (descending)
            usort($filteredServers, function ($a, $b) {
                $playersA = (int) ($a['players'] ?? 0);
                $playersB = (int) ($b['players'] ?? 0);

                return $playersB <=> $playersA;
            });

            // Ensure proper types and preserve UTF-8 encoding
            return array_map(function ($server) {
                $serverName = (string) ($server['name'] ?? '');
                $serverName = mb_convert_encoding($serverName, 'ISO-8859-1', 'UTF-8');

                return [
                    'name' => $serverName,
                    'ip' => (string) ($server['ip'] ?? ''),
                    'port' => (int) ($server['port'] ?? 0),
                    'players' => (int) ($server['players'] ?? 0),
                    'maxplayers' => (int) ($server['maxplayers'] ?? 0),
                    'password' => (int) ($server['password'] ?? 0),
                    'version' => (string) ($server['version'] ?? ''),
                ];
            }, array_values($filteredServers));

        } catch (\Exception $e) {
            Log::error('Exception while fetching MTA servers', [
                'url' => $apiUrl,
                'message' => $e->getMessage(),
            ]);

            // Return cached data if available, otherwise empty array
            return Cache::get(self::CACHE_KEY, []);
        }
    }

    /**
     * Get the timestamp when the server data was last fetched.
     *
     * @return int|null Unix timestamp or null if never fetched
     */
    public function getFetchTimestamp(): ?int
    {
        return Cache::get(self::CACHE_TIMESTAMP_KEY);
    }
}
