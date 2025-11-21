<?php

return [
    /*
    |--------------------------------------------------------------------------
    | MTA Server List API Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for fetching MTA servers from the JSON API.
    |
    */

    'api_url' => env('MTA_API_URL', 'https://multitheftauto.com/api/'),

    'server_version' => env('MTA_SERVER_VERSION', '1.6'),

    'cache_duration' => env('MTA_CACHE_DURATION', 10 * 60), // seconds

    'verify_ssl' => env('MTA_VERIFY_SSL', false), // Set to true in production if SSL certificates are properly configured
];
