<?php

namespace RZP\Tests\Functional\RazorX;

return [
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
