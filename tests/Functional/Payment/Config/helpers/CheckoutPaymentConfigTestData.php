<?php

return [
    'testGetCheckoutConfigWithIdInternal' => [
        'request' => [
            'method' => 'GET',
            'url' => '/internal/payment/config/checkout',
        ],
        'response' => [
            'content' => [
                'restrictions' => [
                    'allow' => [
                        [
                            'iins' => ['400016'],
                            'method' => 'card',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetDefaultCheckoutConfigInternal' => [
        'request' => [
            'method' => 'GET',
            'url' => '/internal/payment/config/checkout',
        ],
        'response' => [
            'content' => [
                'restrictions' => [
                    'allow' => [
                        [
                            'iins' => ['400016'],
                            'method' => 'card',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetDefaultCheckoutConfigInternalWithNoDefaultConfig' => [
        'request' => [
            'method' => 'GET',
            'url' => '/internal/payment/config/checkout',
        ],
        'response' => [
            'content' => []
        ],
    ],
];
