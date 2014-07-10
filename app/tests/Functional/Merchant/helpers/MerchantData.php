<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'createMerchant' => [
        'request' => [
            'content' => [
                'id' => 1000,
            ],
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id' => 1000,
                'key' => [],
            ],
        ],
    ],

    'updateKeyExpireNow' => [
        'request' => [
            'content' => [
            ],
            'url' => '/keys/d9c6bf091a1a64cb5678d8c1',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'old' => [
                ],
                'new' => [
                ]
            ],
        ],
    ],

    'updateKeyExpireInFuture' => [
        'request' => [
            'content' => [
                'delay_roll' => '1'
            ],
            'url' => '/keys/d9c6bf091a1a64cb5678d8c1',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'old' => [
                ],
                'new' => [
                ]
            ],
        ],
    ],

    'updateKeyTwice' => [
        'request' => [
            'content' => [
            ],
            'url' => '/keys/d9c6bf091a1a64cb5678d8c1',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_KEY_EXPIRED,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'code' => ErrorCode::BAD_REQUEST_KEY_EXPIRED,
        ],
    ]
];
