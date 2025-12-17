<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtaStatsService
{
    /**
     * Fetch historical player/server counts from the Node stats service.
     *
     * @return array<int, array{players: int, servers: int, created_at: string}>
     */
    public function getHistory(int $days): array
    {
        $safeDays = max(1, min($days, 365));
        $cacheKey = "mta_stats_history_{$safeDays}";
        $cacheDuration = (int) config('mta.stats_cache_duration', 600);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $baseUrl = rtrim(config('mta.stats_api_url'), '/');
        $url = "{$baseUrl}/history/{$safeDays}";

        try {
            $httpClient = Http::timeout(10);
            if (! config('mta.verify_ssl', false)) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->get($url);

            if (! $response->successful()) {
                Log::warning('Failed to fetch MTA stats history', [
                    'url' => $url,
                    'status' => $response->status(),
                ]);

                return [];
            }

            $data = $response->json();

            if (! is_array($data)) {
                Log::warning('Invalid MTA stats history response', [
                    'url' => $url,
                    'sample' => mb_substr((string) $response->body(), 0, 300, 'UTF-8'),
                ]);

                return [];
            }

            $cleaned = array_values(array_filter($data, static function ($row) {
                return is_array($row)
                    && array_key_exists('players', $row)
                    && array_key_exists('servers', $row)
                    && array_key_exists('created_at', $row);
            }));

            if (count($cleaned) > 0) {
                Cache::put($cacheKey, $cleaned, now()->addSeconds($cacheDuration));
            }

            return $cleaned;
        } catch (\Throwable $e) {
            Log::error('Exception while fetching MTA stats history', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return [];
        }
    }
}
