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

    'merchantFetchKeys' => [
        'request' => [
            'content' => [
            ],
            'url' => 'keys',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'data' => [
                    '0' => [
                        'id' => "d9c6bf091a1a64cb5678d8c1",
                        'merchant_id' => "1",
                        'expired_at' => null
                    ],
                ],
            ]
        ]
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
