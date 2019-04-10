<?php

return [
    'testImplicitVariableOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitFixedOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitOnPaymentCapture' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitForRecordOnly' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testImplicitVariableAndExplicit' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitOnInternationalPayment' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testExplicitPricingRuleAbsent' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],

    'testGSTForPaymentsLessThan2K' => [
        'request' => [
            'method' => 'POST',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
        ],
    ],
];
