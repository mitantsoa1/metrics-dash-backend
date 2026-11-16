<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'docs*', 'contexts/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_unique(array_filter([
        'http://localhost:3000',
        'http://localhost:3001',
        'http://localhost:8000',
        'https://dev.creerailleurs.com',
        'https://api-dev.creerailleurs.com',
        'https://metricsdash.bluevaloris.com',
        'http://dev.creerailleurs.com',
        'http://api-dev.creerailleurs.com',
        'http://metricsdash.bluevaloris.com',
        'https://www.ownsite.tech',
        'https://ownsite.tech',
        env('FRONTEND_URL'),
        ...array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', ''))),
    ]))),

    'allowed_origins_patterns' => [
        '#^https?://.*\.creerailleurs\.com$#',
        '#^https?://.*\.bluevaloris\.com$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => ['X-Correlation-Id'],

    'max_age' => 0,

    'supports_credentials' => true,

];
