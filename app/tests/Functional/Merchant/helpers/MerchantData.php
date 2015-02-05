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
                'email' => 'test@localhost.com',
            ],
            'url' => '/merchants',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'name' => 'Tester',
                'email' => 'test@localhost.com',
                'pricing_plan_id' => '1In3Yh5Mluj605',
            ],
        ],
    ],

    'testCreateKey' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/1X4hRFHFx4UiXt/keys',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'key',
                'expired_at' => null,
            ]
        ]
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
                'items' => [
                    '0' => [
                        'id' => 'rzp_test_TheTestAuthKey',
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
            'url' => '/merchants/10000000000000/keys/rzp_test_TheTestAuthKey',
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
            'url' => '/merchants/10000000000000/keys/rzp_test_TheTestAuthKey',
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
            'url' => '/merchants/10000000000000/keys/rzp_test_TheTestAuthKey',
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

    'testRollDemoKey' => [
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
                    'description' => PublicErrorDescription::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
        ],
    ],

    'testActivateMerhantWithoutBankAccount' => [
        'request' => [
            'content' => [],
            'url' => '/merchants/1cXSLlUU8V9sXl/activate',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NO_BANK_ACCOUNT_FOUND,
        ]
    ],

    'testActivateMerchant' => [
        'request' => [
            'content' => [],
            'url' => '/merchants/1cXSLlUU8V9sXl/activate',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => true,
                'live' => true,
            ],
        ],
    ],

    'testMerchantEnableLive' => [
        'request' => [
            'content' => [],
            'url' => '/merchants/1cXSLlUU8V9sXl/live/enable',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => true,
                'live' => true,
            ]
        ]
    ],

    'testMerchantDisableLive' => [
        'request' => [
            'content' => [],
            'url' => '/merchants/1cXSLlUU8V9sXl/live/disable',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => true,
                'live' => false,
            ]
        ]
    ],

    'testAddBankAccount' => [
        'request' => [
            'content' => [
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test beneficiary random name',
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test beneficiary random name',
            ]
        ]
    ],

    'testGetBankAccount' => [
        'request' => [
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test beneficiary random name',
            ]
        ]
    ],

    'testSetBanks' => [
        'request' => [
            'url' => '/merchants/10000000000000/banks',
            'method' => 'POST',
            'content' => [
                'banks' => [
                    'HDFC',
                    'ICIC',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'banks' => [
                    'HDFC',
                    'ICIC',
                ]
            ],
        ]
    ],

    'testGetBanksByMerchantAuth' => [
        'request' => [
            'url' => '/banks',
            'method' => 'GET',
            'content' => [
                'callback' => 'abcdef',
                '_' => 'abcdef',
            ]
        ],
        'response' => [
            'content' => [
                'HDFC' => 'HDFC Bank',
                'ICIC' => 'ICICI Bank',
            ],
        ],
        'jsonp' => true
    ],

    'testGetBanksByAppAuth' => [
        'request' => [
            'url' => '/merchants/10000000000000/banks',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank',
                    'ICIC' => 'ICICI Bank',
                ],
                'disabled' => [
                    'YESB' => 'Yes Bank',
                    'VIJB' => 'Vijaya Bank',
                ]
            ],
        ],
    ],
];
