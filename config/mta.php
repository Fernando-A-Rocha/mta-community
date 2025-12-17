<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSL Verification Configuration
    |--------------------------------------------------------------------------
    | Configuration for verifying SSL certificates for HTTP requests.
    */
    'verify_ssl' => env('MTA_VERIFY_SSL', false), // Set to true in production if SSL certificates are properly configured

    /*
    |--------------------------------------------------------------------------
    | MTA Server List API Configuration
    |--------------------------------------------------------------------------
    | Configuration for fetching MTA servers from the JSON API.
    */

    'servers_api_url' => 'https://multitheftauto.com/api/',

    'current_stable_version' => '1.6',

    /*
    |--------------------------------------------------------------------------
    | MTA Stats Cache
    |--------------------------------------------------------------------------
    | Configuration for caching MTA server related stats.
    */
    'stats_api_url' => env('MTA_STATS_API_URL', 'http://localhost:3069'),
    'stats_cache_duration' => 60 * 10, // seconds (10 minutes)
    'servers_cache_duration' => 60 * 10, // seconds (10 minutes)
    /*
    |--------------------------------------------------------------------------
    | MTA News Configuration
    |--------------------------------------------------------------------------
    | Configuration for fetching and caching MTA forum news entries.
    */

    'news_forum_url' => 'https://multitheftauto.com/news',

    'news_cache_duration' => 60 * 60 * 12, // seconds (12 hours)

    /*
    |--------------------------------------------------------------------------
    | GitHub Activity Configuration
    |--------------------------------------------------------------------------
    | Configuration for fetching and caching GitHub activity (commits, issues, releases).
    */

    'github_token' => env('GITHUB_TOKEN'),

    'github_activity_cache_duration' => 60 * 15, // seconds (15 minutes)
];
