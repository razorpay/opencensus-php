<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testCreateMerchant' => [
        'request' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
                'name'  => 'Tester',
                'email' => 'test@localhost.com'
            ],
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
            ],
        ],
    ],

    'testGetMerchant' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'    => '1X4hRFHFx4UiXt',
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
            'url' => '/merchants/10000000000000/keys',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'data' => [
                    '0' => [
                        'id' => 'rzp_test_1DP5mmOlF5G5ag',
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
            'url' => '/merchants/10000000000000/keys/rzp_test_1DP5mmOlF5G5ag',
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
            'url' => '/merchants/10000000000000/keys/rzp_test_1DP5mmOlF5G5ag',
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
            'url' => '/merchants/10000000000000/keys/rzp_test_1DP5mmOlF5G5ag',
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

    'testNewKeyIdRandom' => [
        'request' => [
            'url' => '/merchants/10000000000000/keys/rzp_test_1DP5mmOlF5G5ag',
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
    ]
];
