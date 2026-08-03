<?php

return [
    'title' => 'MetricsDash API',
    'description' => 'SaaS Analytics Enterprise Multi-Tenant API',
    'version' => '1.0.0',
    'prefix' => '/api',
    'route_prefix' => '/api',
    'routes' => [
        'prefix' => '/api',
    ],
    'resources' => [
        app_path('Domains/IAM/Resources'),
        app_path('Domains/Instance/Resources'),
        app_path('Domains/Ingestion/Resources'),
    ],

    'formats' => [
        'jsonld' => ['application/ld+json'],
        'json' => ['application/json'],
        'html' => ['text/html'],
    ],
    'patch_formats' => [
        'json' => ['application/merge-patch+json'],
    ],
    'error_formats' => [
        'jsonproblem' => ['application/problem+json'],
        'jsonld' => ['application/ld+json'],
    ],
    'defaults' => [
        'pagination_enabled' => true,
        'pagination_items_per_page' => 30,
        'pagination_client_items_per_page' => true,
    ],


];
