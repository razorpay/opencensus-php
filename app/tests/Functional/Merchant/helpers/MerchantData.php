<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreateMerchant' => [
        'request' => [
            'content' => [
                'id'    => '41ce4abda390575910cba897',
                'name'  => 'Tester',
                'email' => 'test@localhost.com'
            ],
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id' => '41ce4abda390575910cba897',
            ],
        ],
    ],

    'testGetMerchant' => [
        'request' => [
            'url' => '/merchants/41ce4abda390575910cba897',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'    => '41ce4abda390575910cba897',
                'entity' => 'merchant',
                'name'  => 'Tester',
                'email' => 'liveAndTest@localhost.com',
            ],
        ],
    ],

    'testMerchantFetchKeys' => [
        'request' => [
            'content' => [
            ],
            'url' => '/merchants/363e4efa820b0c06208ccd99/keys',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'data' => [
                    '0' => [
                        'id' => 'rzp_test_d9c6bf091a1a64cb5678d8c1',
                        'expired_at' => null
                    ],
                ],
            ]
        ]
    ],

    'testUpdateKeyExpireNow' => [
        'request' => [
            'content' => [
            ],
            'url' => '/merchants/363e4efa820b0c06208ccd99/keys/rzp_test_d9c6bf091a1a64cb5678d8c1',
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

    'testUpdateKeyExpireInFuture' => [
        'request' => [
            'content' => [
                'delay_roll' => '1'
            ],
            'url' => '/merchants/363e4efa820b0c06208ccd99/keys/rzp_test_d9c6bf091a1a64cb5678d8c1',
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

    'testUpdateKeyTwice' => [
        'request' => [
            'content' => [
            ],
            'url' => '/merchants/363e4efa820b0c06208ccd99/keys/rzp_test_d9c6bf091a1a64cb5678d8c1',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_KEY_EXPIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_KEY_EXPIRED,
        ],
    ],
];
