<?php

return [
    // If mock is true, no remote http call will be made and sample hard-coded
    // response will be sent.
    'mock' => env('STORK_MOCK'),

    // Api url for stork's application.
    'url' => env('STORK_URL'),

    // Authentication for api calls to stork for each mode.
    // Each mode's username acts as owning service name as well.
    'auth' => [
        'live' => [
            'user' => 'api-live',
            'pass' => env('STORK_AUTH_USER_LIVE'),
        ],
        'test' => [
            'user' => 'api-test',
            'pass' => env('STORK_AUTH_USER_TEST'),
        ],
    ],
];
