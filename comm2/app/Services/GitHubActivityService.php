<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubActivityService
{
    private const CACHE_KEY_PREFIX = 'github_activity_';

    private const CACHE_TIMESTAMP_KEY_PREFIX = 'github_activity_timestamp_';

    private const API_BASE_URL = 'https://api.github.com';

    private const REPOSITORIES = [
        'mtasa-blue' => 'multitheftauto/mtasa-blue',
        'mtasa-resources' => 'multitheftauto/mtasa-resources',
    ];

    /**
     * Get cached GitHub activity for a specific repository.
     *
     * @param  string  $repoKey  Repository key ('mtasa-blue' or 'mtasa-resources')
     * @return array<int, array{type: string, title: string, author: string, date: Carbon, url: string}>
     */
    public function getActivity(string $repoKey): array
    {
        if (! isset(self::REPOSITORIES[$repoKey])) {
            return [];
        }

        $cacheKey = self::CACHE_KEY_PREFIX.$repoKey;
        $timestampKey = self::CACHE_TIMESTAMP_KEY_PREFIX.$repoKey;
        $cacheDuration = (int) config('mta.github_activity_cache_duration', 900);

        // Check if we need to refresh the cache
        $lastFetch = Cache::get($timestampKey);
        $needsRefresh = $lastFetch === null || (time() - $lastFetch) > $cacheDuration;
        // $needsRefresh = true; // Bypass cache for testing

        if ($needsRefresh) {
            $activity = $this->fetchActivity($repoKey);
            // Only cache if we have activity, so we can retry immediately if we get 0 entries
            if (count($activity) > 0) {
                // Convert Carbon dates to ISO strings for caching
                $serializedActivity = $this->serializeActivityForCache($activity);
                Cache::put($cacheKey, $serializedActivity, now()->addHours(24)); // Store for 24 hours as backup
                Cache::put($timestampKey, time(), now()->addHours(24));
            }
        } else {
            $serializedActivity = Cache::get($cacheKey, []);
            // Convert ISO strings back to Carbon dates
            $activity = $this->deserializeActivityFromCache($serializedActivity);
        }

        return $activity;
    }

    /**
     * Fetch activity from GitHub API for a repository.
     *
     * @param  string  $repoKey  Repository key
     * @return array<int, array{type: string, title: string, author: string, date: Carbon, url: string}>
     */
    private function fetchActivity(string $repoKey): array
    {
        $repo = self::REPOSITORIES[$repoKey];
        $token = config('mta.github_token');

        if (empty($token)) {
            Log::warning('GITHUB_TOKEN not configured');
            $cached = Cache::get(self::CACHE_KEY_PREFIX.$repoKey, []);

            return $this->deserializeActivityFromCache($cached);
        }

        $allActivity = [];

        try {
            // Fetch commits
            $commits = $this->fetchCommits($repo, $token);
            foreach ($commits as $commit) {
                $allActivity[] = [
                    'type' => 'commit',
                    'title' => $commit['message'],
                    'author' => $commit['author'],
                    'date' => $commit['date'],
                    'url' => $commit['url'],
                ];
            }

            // Fetch issues (includes PRs)
            $issues = $this->fetchIssues($repo, $token);
            foreach ($issues as $issue) {
                $allActivity[] = [
                    'type' => $issue['is_pr'] ? 'pull_request' : 'issue',
                    'title' => $issue['title'],
                    'author' => $issue['author'],
                    'date' => $issue['date'],
                    'url' => $issue['url'],
                ];
            }

            // Fetch releases
            $releases = $this->fetchReleases($repo, $token);
            foreach ($releases as $release) {
                $allActivity[] = [
                    'type' => 'release',
                    'title' => $release['title'],
                    'author' => $release['author'],
                    'date' => $release['date'],
                    'url' => $release['url'],
                ];
            }

            // Sort by date (descending - most recent first)
            usort($allActivity, function ($a, $b) {
                return $b['date']->timestamp <=> $a['date']->timestamp;
            });

            // Limit to 20 total activities (the 20 most recent overall)
            return array_slice($allActivity, 0, 20);

        } catch (\Exception $e) {
            Log::error('Exception while fetching GitHub activity', [
                'repo' => $repo,
                'message' => $e->getMessage(),
            ]);

            $cached = Cache::get(self::CACHE_KEY_PREFIX.$repoKey, []);

            return $this->deserializeActivityFromCache($cached);
        }
    }

    /**
     * Fetch commits from GitHub API.
     *
     * @param  string  $repo  Repository in format 'owner/repo'
     * @param  string  $token  GitHub token
     * @return array<int, array{message: string, author: string, date: Carbon, url: string}>
     */
    private function fetchCommits(string $repo, string $token): array
    {
        $url = self::API_BASE_URL."/repos/{$repo}/commits";
        $response = $this->makeRequest($url, $token, ['per_page' => 20]);

        if (! $response || ! is_array($response)) {
            return [];
        }

        $commits = [];
        foreach ($response as $commit) {
            $commits[] = [
                'message' => $this->extractCommitMessage($commit['commit']['message'] ?? ''),
                'author' => $commit['commit']['author']['name'] ?? $commit['author']['login'] ?? 'Unknown',
                'date' => Carbon::parse($commit['commit']['author']['date'] ?? 'now'),
                'url' => $commit['html_url'] ?? '',
            ];
        }

        return $commits;
    }

    /**
     * Fetch issues and pull requests from GitHub API.
     *
     * @param  string  $repo  Repository in format 'owner/repo'
     * @param  string  $token  GitHub token
     * @return array<int, array{title: string, author: string, date: Carbon, url: string, is_pr: bool}>
     */
    private function fetchIssues(string $repo, string $token): array
    {
        $url = self::API_BASE_URL."/repos/{$repo}/issues";
        $response = $this->makeRequest($url, $token, ['per_page' => 20, 'state' => 'all']);

        if (! $response || ! is_array($response)) {
            return [];
        }

        $issues = [];
        foreach ($response as $issue) {
            $issues[] = [
                'title' => $issue['title'] ?? '',
                'author' => $issue['user']['login'] ?? 'Unknown',
                'date' => Carbon::parse($issue['created_at'] ?? 'now'),
                'url' => $issue['html_url'] ?? '',
                'is_pr' => isset($issue['pull_request']),
            ];
        }

        return $issues;
    }

    /**
     * Fetch releases from GitHub API.
     *
     * @param  string  $repo  Repository in format 'owner/repo'
     * @param  string  $token  GitHub token
     * @return array<int, array{title: string, author: string, date: Carbon, url: string}>
     */
    private function fetchReleases(string $repo, string $token): array
    {
        $url = self::API_BASE_URL."/repos/{$repo}/releases";
        $response = $this->makeRequest($url, $token, ['per_page' => 20]);

        if (! $response || ! is_array($response)) {
            return [];
        }

        $releases = [];
        foreach ($response as $release) {
            $releases[] = [
                'title' => $release['name'] ?? $release['tag_name'] ?? '',
                'author' => $release['author']['login'] ?? 'Unknown',
                'date' => Carbon::parse($release['published_at'] ?? $release['created_at'] ?? 'now'),
                'url' => $release['html_url'] ?? '',
            ];
        }

        return $releases;
    }

    /**
     * Make HTTP request to GitHub API.
     *
     * @param  string  $url  API URL
     * @param  string  $token  GitHub token
     * @param  array<string, mixed>  $params  Query parameters
     * @return array<mixed>|null
     */
    private function makeRequest(string $url, string $token, array $params = []): ?array
    {
        try {
            $verifySsl = (bool) config('mta.verify_ssl', false);
            $httpClient = Http::timeout(15)
                ->withHeaders([
                    'Accept' => 'application/vnd.github.v3+json',
                    'Authorization' => "Bearer {$token}",
                    'User-Agent' => 'MTA-Community-App',
                ]);
            if (! $verifySsl) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->get($url, $params);

            if (! $response->successful()) {
                Log::warning('Failed to fetch from GitHub API', [
                    'status' => $response->status(),
                    'url' => $url,
                ]);

                return null;
            }

            return $response->json();

        } catch (\Exception $e) {
            Log::error('Exception while making GitHub API request', [
                'url' => $url,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Extract first line of commit message (title).
     */
    private function extractCommitMessage(string $message): string
    {
        $lines = explode("\n", $message);
        $firstLine = trim($lines[0] ?? '');
        // Limit length for display
        if (strlen($firstLine) > 100) {
            return substr($firstLine, 0, 97).'...';
        }

        return $firstLine;
    }

    /**
     * Serialize activity entries for caching (convert Carbon to ISO string).
     *
     * @param  array<int, array{type: string, title: string, author: string, date: Carbon, url: string}>  $activity
     * @return array<int, array{type: string, title: string, author: string, date: string, url: string}>
     */
    private function serializeActivityForCache(array $activity): array
    {
        return array_map(function ($entry) {
            return [
                'type' => $entry['type'],
                'title' => $entry['title'],
                'author' => $entry['author'],
                'date' => $entry['date']->toIso8601String(),
                'url' => $entry['url'],
            ];
        }, $activity);
    }

    /**
     * Deserialize activity entries from cache (convert ISO string to Carbon).
     *
     * @param  array<int, array{type: string, title: string, author: string, date: string, url: string}>  $serializedActivity
     * @return array<int, array{type: string, title: string, author: string, date: Carbon, url: string}>
     */
    private function deserializeActivityFromCache(array $serializedActivity): array
    {
        return array_map(function ($entry) {
            return [
                'type' => $entry['type'],
                'title' => $entry['title'],
                'author' => $entry['author'],
                'date' => Carbon::parse($entry['date']),
                'url' => $entry['url'],
            ];
        }, $serializedActivity);
    }
}
