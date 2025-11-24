<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use DOMDocument;
use DOMXPath;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MtaNewsService
{
    private const CACHE_KEY = 'mta_news_list';

    private const CACHE_TIMESTAMP_KEY = 'mta_news_list_timestamp';

    /**
     * Get cached and sorted MTA news entries.
     *
     * @return array<int, array{url: string, title: string, author: string, date: Carbon}>
     */
    public function getNews(): array
    {
        $cacheKey = self::CACHE_KEY;
        $timestampKey = self::CACHE_TIMESTAMP_KEY;
        $cacheDuration = (int) config('mta.news_cache_duration', 3600); // Default 1 hour

        // Check if we need to refresh the cache
        $lastFetch = Cache::get($timestampKey);
        $needsRefresh = $lastFetch === null || (time() - $lastFetch) > $cacheDuration;
        // $needsRefresh = true; // Bypass cache for testing

        if ($needsRefresh) {
            $news = $this->fetchAndParseNews();
            // Only cache if we have news, so we can retry immediately if we get 0 entries
            if (count($news) > 0) {
                // Convert Carbon dates to ISO strings for caching
                $serializedNews = $this->serializeNewsForCache($news);
                Cache::put($cacheKey, $serializedNews, now()->addHours(24)); // Store for 24 hours as backup
                Cache::put($timestampKey, time(), now()->addHours(24));
            }
        } else {
            $serializedNews = Cache::get($cacheKey, []);
            // Convert ISO strings back to Carbon dates
            $news = $this->deserializeNewsFromCache($serializedNews);
        }

        return $news;
    }

    /**
     * Fetch and parse news from the forum.
     *
     * @return array<int, array{url: string, title: string, author: string, date: Carbon}>
     */
    private function fetchAndParseNews(): array
    {
        try {
            $httpClient = Http::timeout(15)
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                ]);
            $verifySsl = (bool) config('mta.verify_ssl', false);
            if (! $verifySsl) {
                $httpClient = $httpClient->withoutVerifying();
            }
            $forumUrl = (string) config('mta.news_forum_url');

            $response = $httpClient->get($forumUrl);

            if (! $response->successful()) {
                Log::warning('Failed to fetch MTA news', [
                    'status' => $response->status(),
                    'url' => $forumUrl,
                ]);

                $cached = Cache::get(self::CACHE_KEY, []);

                return $this->deserializeNewsFromCache($cached);
            }

            $html = $response->body();
            $news = $this->parseHtml($html);

            // Sort by date (descending - most recent first)
            usort($news, function ($a, $b) {
                return $b['date']->timestamp <=> $a['date']->timestamp;
            });

            return $news;

        } catch (\Exception $e) {
            Log::error('Exception while fetching MTA news', [
                'url' => $forumUrl,
                'message' => $e->getMessage(),
            ]);

            $cached = Cache::get(self::CACHE_KEY, []);

            return $this->deserializeNewsFromCache($cached);
        }
    }

    /**
     * Parse HTML content to extract news entries.
     *
     * @return array<int, array{url: string, title: string, author: string, date: Carbon}>
     */
    private function parseHtml(string $html): array
    {
        $news = [];
        $seenUrls = [];

        // Suppress warnings for malformed HTML
        libxml_use_internal_errors(true);

        $dom = new DOMDocument;
        @$dom->loadHTML('<?xml encoding="UTF-8">'.$html);

        $xpath = new DOMXPath($dom);

        // Find all links to topics (thread URLs)
        // Only use links that are in headings (main topic titles) to avoid pagination and other non-news links
        // Pattern: /topic/123456-title-slug/
        $topicLinks = $xpath->query("//h1//a[contains(@href, '/topic/')] | //h2//a[contains(@href, '/topic/')] | //h3//a[contains(@href, '/topic/')] | //h4//a[contains(@href, '/topic/')] | //h5//a[contains(@href, '/topic/')] | //h6//a[contains(@href, '/topic/')]");

        foreach ($topicLinks as $link) {
            $href = $link->getAttribute('href');
            $title = trim($link->textContent);

            // Skip if no title or invalid href
            if (empty($title) || empty($href)) {
                continue;
            }

            // Skip pagination buttons and very short/numeric-only titles
            // Pagination buttons are typically single digits, "Next", "Previous", etc.
            $titleLength = mb_strlen($title);
            if ($titleLength < 3 || ctype_digit($title) ||
                in_array(strtolower($title), ['next', 'prev', 'previous', 'first', 'last', '»', '«', 'page'])) {
                continue;
            }

            // Skip if title is just a number or very short (likely pagination)
            // Real news titles should be at least 5 characters
            if ($titleLength < 5 && preg_match('/^\d+$/', $title)) {
                continue;
            }

            // Make URL absolute if relative
            $url = $href;
            if (! str_starts_with($url, 'http')) {
                $url = 'https://forum.multitheftauto.com'.ltrim($url, '/');
            }

            // Skip if we've already seen this URL
            if (isset($seenUrls[$url])) {
                continue;
            }

            // Additional check: ensure URL is actually a topic URL
            if (! str_contains($url, '/topic/')) {
                continue;
            }

            // Skip if URL looks like pagination or navigation
            if (str_contains($url, '/page/') || str_contains($url, '#comments') || str_contains($url, '#el')) {
                continue;
            }

            // Find the parent container (listitem or similar) that contains this link
            $container = $link;
            for ($i = 0; $i < 10; $i++) {
                $container = $container->parentNode;
                if ($container === null) {
                    break;
                }

                // Look for author and date within this container
                $author = $this->extractAuthorFromContainer($xpath, $container);
                $date = $this->extractDateFromContainer($xpath, $container);

                // If we found both author and date, we have a complete entry
                if ($author !== null && $date !== null) {
                    $seenUrls[$url] = true;
                    $news[] = [
                        'url' => $url,
                        'title' => $title,
                        'author' => $author,
                        'date' => $date,
                    ];
                    break;
                }
            }
        }

        libxml_clear_errors();

        return $news;
    }

    /**
     * Extract author from a container element.
     */
    private function extractAuthorFromContainer(DOMXPath $xpath, \DOMElement $container): ?string
    {
        // Look for links to profile pages (author links)
        // Pattern: /profile/709-jhxp/
        $profileLinks = $xpath->query(".//a[contains(@href, '/profile/')]", $container);

        if ($profileLinks->length > 0) {
            // Get the first profile link that appears after "By" text
            foreach ($profileLinks as $profileLink) {
                $author = trim($profileLink->textContent);
                if (! empty($author)) {
                    // Check if there's "By" text before this link in the same container
                    $textBefore = '';
                    $node = $profileLink->previousSibling;
                    while ($node !== null) {
                        if ($node->nodeType === XML_TEXT_NODE) {
                            $textBefore = $node->textContent.$textBefore;
                        }
                        $node = $node->previousSibling;
                    }

                    // Also check parent's text content for "By"
                    $parentText = $container->textContent ?? '';
                    if (stripos($parentText, 'By') !== false || stripos($textBefore, 'By') !== false) {
                        return $author;
                    }
                }
            }

            // If no "By" found but we have profile links, use the first one
            $author = trim($profileLinks->item(0)->textContent);
            if (! empty($author)) {
                return $author;
            }
        }

        return null;
    }

    /**
     * Extract date from a container element.
     */
    private function extractDateFromContainer(DOMXPath $xpath, \DOMElement $container): ?Carbon
    {
        // First, look for time elements (most reliable)
        $timeElements = $xpath->query('.//time', $container);
        if ($timeElements->length > 0) {
            foreach ($timeElements as $timeElement) {
                // Try datetime attribute first
                $datetime = $timeElement->getAttribute('datetime');
                if (! empty($datetime)) {
                    try {
                        $date = Carbon::parse($datetime);
                        if ($date->isValid() && $date->year >= 2000 && $date->year <= 2100) {
                            return $date;
                        }
                    } catch (\Exception $e) {
                        // Continue
                    }
                }

                // Try text content of time element
                $timeText = trim($timeElement->textContent);
                if (! empty($timeText)) {
                    try {
                        $date = Carbon::parse($timeText);
                        if ($date->isValid() && $date->year >= 2000 && $date->year <= 2100) {
                            return $date;
                        }
                    } catch (\Exception $e) {
                        // Continue
                    }
                }
            }
        }

        // Fallback: look for date patterns in text content
        $text = $container->textContent ?? '';
        $datePatterns = [
            '/([A-Z][a-z]+)\s+(\d{1,2}),\s+(\d{4})/', // December 24, 2024
            '/([A-Z][a-z]{2,3})\s+(\d{1,2}),\s+(\d{4})/', // Dec 24, 2024
            '/(\d{1,2})\s+([A-Z][a-z]+)\s+(\d{4})/', // 24 December 2024
            '/(\d{4})-(\d{2})-(\d{2})/', // 2024-12-24
        ];

        foreach ($datePatterns as $pattern) {
            if (preg_match($pattern, $text, $matches)) {
                try {
                    $dateString = $matches[0];
                    $date = Carbon::parse($dateString);
                    if ($date->isValid() && $date->year >= 2000 && $date->year <= 2100) {
                        return $date;
                    }
                } catch (\Exception $e) {
                    // Continue to next pattern
                }
            }
        }

        return null;
    }

    /**
     * Serialize news entries for caching (convert Carbon to ISO string).
     *
     * @param  array<int, array{url: string, title: string, author: string, date: Carbon}>  $news
     * @return array<int, array{url: string, title: string, author: string, date: string}>
     */
    private function serializeNewsForCache(array $news): array
    {
        return array_map(function ($entry) {
            return [
                'url' => $entry['url'],
                'title' => $entry['title'],
                'author' => $entry['author'],
                'date' => $entry['date']->toIso8601String(),
            ];
        }, $news);
    }

    /**
     * Deserialize news entries from cache (convert ISO string to Carbon).
     *
     * @param  array<int, array{url: string, title: string, author: string, date: string}>  $serializedNews
     * @return array<int, array{url: string, title: string, author: string, date: Carbon}>
     */
    private function deserializeNewsFromCache(array $serializedNews): array
    {
        return array_map(function ($entry) {
            return [
                'url' => $entry['url'],
                'title' => $entry['title'],
                'author' => $entry['author'],
                'date' => Carbon::parse($entry['date']),
            ];
        }, $serializedNews);
    }
}
