<?php

return [
    /*
    |--------------------------------------------------------------------------
    | SSL Verification Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for verifying SSL certificates for HTTP requests.
    |
    */
    'verify_ssl' => env('MTA_VERIFY_SSL', false), // Set to true in production if SSL certificates are properly configured

    /*
    |--------------------------------------------------------------------------
    | MTA Server List API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for fetching MTA servers from the JSON API.
    |
    */

    'servers_api_url' => 'https://multitheftauto.com/api/',

    'current_stable_version' => '1.6',

    'servers_cache_duration' => env('MTA_SERVERS_CACHE_DURATION', 10 * 60), // seconds
    /*
    |--------------------------------------------------------------------------
    | MTA News Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for fetching and caching MTA forum news entries.
    |
    */

    'news_forum_url' => 'https://multitheftauto.com/news',

    'news_cache_duration' => env('MTA_NEWS_CACHE_DURATION', 3600), // seconds (default: 1 hour)

    /*
    |--------------------------------------------------------------------------
    | GitHub Activity Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for fetching and caching GitHub activity (commits, issues, releases).
    |
    */

    'github_token' => env('GITHUB_TOKEN'),

    'github_activity_cache_duration' => env('MTA_GITHUB_ACTIVITY_CACHE_DURATION', 900), // seconds (default: 15 minutes)
];
