<?php

return [
    // If mock is true, no remote http call will be made and sample hard-coded
    // response will be sent.
    'mock' => env('STORK_MOCK'),

    // Api url for stork's application.
    'url' => env('STORK_URL'),

    // Prefix used in service name. E.g. beta-, delta- etc.
    // Service name is same as authenticated user usually.
    // But except production on staging, func, qa etc we share one instance
    // of stork with multiple instances of api and/or dashboard setup. In
    // that case we use different service names. We could have used separate
    // user/pass itself but that requires unnecessary configurations for tens
    // of stage instances in codebase both side.
    'service_prefix' => env('STORK_SERVICE_PREFIX'),

    // Authentication for api calls to stork for each mode.
    // Each mode's username acts as owning service name as well.
    'auth' => [
        'primary' => [
            'live' => [
                'user' => 'api-live',
                'pass' => env('STORK_AUTH_USER_LIVE'),
            ],
            'test' => [
                'user' => 'api-test',
                'pass' => env('STORK_AUTH_USER_TEST'),
            ],
        ],
        'banking' => [
            'live' => [
                'user' => 'rx-live',
                'pass' => env('STORK_AUTH_USER_LIVE'),
            ],
            'test' => [
                'user' => 'rx-test',
                'pass' => env('STORK_AUTH_USER_TEST'),
            ],
        ],
    ],
];
