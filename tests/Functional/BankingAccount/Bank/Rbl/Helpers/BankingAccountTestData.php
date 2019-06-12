<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateBankingAccount' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '560034',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
                'status'      => 'created'
            ],
        ],
    ],

    'testCreateBankingAccountWithUnserviceablePincode' => [
        'request'  => [
            'url'     => '/banking_accounts',
            'method'  => 'POST',
            'content' => [
                'channel' => 'rbl',
                'pincode' => '899090',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'channel'     => 'rbl',
                'status'      => 'unserviceable'
            ],
        ],
    ],

    'testCreateBankingAccountWithEmptyPincode' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The pincode field is required when channel is rbl.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateBankingAccountWithInvalidBank' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected channel is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testStoreMerchantCredentials' => [
        'request'  => [
            'url'     => '/banking_account/{id}/merchant_credentials',
            'method'  => 'POST',
            'content' => [
                'subcorp_id'            => 'MERCHANT_SUB_CORP',
                'subcorp_user_id'       => 'MERCHANT_1234',
                'subcorp_user_password' => 'MERCHANT_TEST_PASSWORD'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testStoreMerchantCredentialsFailed' => [
        'request'  => [
            'url'     => '/banking_account/{id}/merchant_credentials',
            'method'  => 'POST',
            'content' => [
                'subcorp_id'            => 'MERCHANT_SUB_CORP',
                'subcorp_user_id'       => 'MERCHANT_1234',
                'subcorp_user_password' => 'MERCHANT_TEST_PASSWORD'
            ],
        ],
        'response' => [
            'content' => [
                'success' => false,
            ],
        ],
    ],
];
