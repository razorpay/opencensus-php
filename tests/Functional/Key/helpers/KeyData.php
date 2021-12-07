<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testNewKeyIdRandom' => [
        'request' => [
            'url' => '/keys/rzp_test_TheTestAuthKey',
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

    'testNewKeyWithOtp' => [
        'request' => [
            'url' => '/keys/otp/rzp_test_AltTestAuthKey',
            'method' => 'PUT',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0007',
            ]
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

    'testNewKeyWithWrongOtp' => [
        'request' => [
            'url' => '/keys/otp/rzp_test_AltTestAuthKey',
            'method' => 'PUT',
            'content' => [
                'token'        => 'BUIj3m2Nx2VvVj',
                'otp'          => '0009',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INCORRECT_OTP
        ]
    ],

    'testRegenerateKeyWhereMerchantIdIsDifferent' => [
        'request' => [
            'url' => '',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testGetKeys' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetKeysByNonOwnerUser' => [
        'request'  => [
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testGetKeysByEPosUser' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'         => 'rzp_test_AltTestAuthKey',
                        'entity'     => 'key',
                        'expired_at' => NULL,
                    ],
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testCaActivatedMerchantCanCreateKeys' => [
        'request' => [
            'url'    => '/keys',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'key'
            ],
        ],
        'status_code' => 200
    ],

    'testCaActivatedMerchantCanCreateKeysWithOtp' => [
        'request' => [
            'url'    => '/keys/otp',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' =>[
                'otp' => '0007',
                'token' => 'BUIj3m2Nx2VvVj'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'key'
            ],
        ],
        'status_code' => 200
    ],

    'testNonCaActivatedMerchantCannotCreateKeys' => [
        'request' => [
            'url'    => '/keys',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NO_KEY_ACCESS,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NO_KEY_ACCESS,
        ]
    ],

    'testBulkRegenerateApiKey' => [
        'request'  => [
            'content' => [
                'merchant_ids'  => [ 'random_mid' ],
                'reason'        => "This is test reason"
            ],
            'url'     => '/regenerate-api-key/bulk',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'success_mids'  => [ ],
                'failed_mids'   => [ 'random_mid' => 'Merchant not found/Invalid Merchant Id' ]
            ],
            'status_code' => 200,
        ],
    ],



];
