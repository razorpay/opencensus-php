<?php

namespace RZP\Tests\Functional\RazorX;

return [
    'testGetTreatmentWithHeaders' => [
        'request' => [
            'url'     => '/dummy/razorx',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'variant' => 'new_header_flow',
            ],
        ],
    ],

    'testGetTreatmentWithCookies' => [
        'request' => [
            'url'     => '/dummy/razorx',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'variant' => 'new_cookie_flow',
            ],
        ],
    ],

    'testGetTreatmentFallbackToHeaders' => [
        'request' => [
            'url'     => '/dummy/razorx',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'variant' => 'fallback_header_flow',
            ],
        ],
    ],

    'testGetTreatmentFallbackToService' => [
        'request' => [
            'url'     => '/dummy/razorx',
            'method'  => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'variant' => 'control',
            ],
        ],
    ],
];
