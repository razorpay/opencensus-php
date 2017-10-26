<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
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

    'testCreateKeyForNonActivatedMerchant' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchants/1X4hRFHFx4UiXt/keys',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_KEY_CREATE_FAILED,
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
                'name'  => 'Tester 2',
                'email' => 'liveandtest@localhost.com',
                'activated' => false,
                'activated_at' => null,
                'methods' => [
                    'merchant_id' => '1X4hRFHFx4UiXt',
                    'paytm' => false,
                    'disabled_banks' => [],
                ]
            ],
        ],
    ],

    'testGetMerchantUsers' => [
        'request' => [
            'url' => '/merchants/1X4hRFHFx4UiXt/users',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                [
                    'role' => 'owner'
                ],
                [
                    'role' => 'manager'
                ]
            ],
        ],
    ],

    'testGetBalance' => [
        'request' => [
            'url' => '/balance',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'id'    => '10000000000000',
                'balance' => 1000000,
            ],
        ],
    ],

    'testGetAccountConfig' => [
        'request' => [
            'url' => '/account/config',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'brand_color' => null,
                'transaction_report_email' => [
                    'test@razorpay.com'
                ]
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
            'class' => RZP\Exception\BadRequestException::class,
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
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
        ],
    ],

    'testEditMerchant' => [
        'request' => [
            'content' => [
                'international' => '1',
                'linked_account_kyc' => '1',
                'website' => 'http://abc.com',
                'category' => '1111',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ]
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                // Case: In sign-up case we will not have any other headers
                // (eg. X-Dashboard-User-Email etc) from dashboard.
                'HTTP_X-Dashboard' => 'true',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'international' => true,
                'linked_account_kyc' => true,
                'category' => 1111,
                'website' => 'http://abc.com',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ]
            ]
        ]
    ],

    'testEditMerchantEnableInternationalFail' => [
        'request' => [
            'content' => [
                'international' => '1',
            ],
            'url' => '/merchants/10000000000000',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    // 'description' => PublicErrorDescription::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditTransactionEmailWithCsv' => [
        'request' => [
            'content' => [
                'transaction_report_email'  => [
                    'test@razorpay.com',
                    'test2@razorpay.com'
                ]
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'transaction_report_email'  => [
                    'test@razorpay.com',
                    'test2@razorpay.com'
                ]
            ]
        ]
    ],

    'testEditTransactionEmailWithError' => [
        'request' => [
            'content' => [
                'transaction_report_email'  => [
                    'test@razorpay.com',
                    'test2razorpay.com'
                ]
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The provided transaction report email is invalid: test2razorpay.com',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantEmail' => [
        'request' => [
            'content' => [
                'email' => 'shake@razorpay.com',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'email' => 'shake@razorpay.com'
            ]
        ]
    ],

    'testEditMerchantUppercaseEmail' => [
        'request' => [
            'content' => [
                'email' => 'UPPERCASE@Razorpay.com',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'email' => 'uppercase@razorpay.com'
            ]
        ]
    ],

    'testEditMerchantEmptyEmail' => [
        'request' => [
            'content' => [
                'email' => '',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt/email',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The email field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantConfig' => [
        'request' => [
            'content' => [
                'brand_color' => '00bcd4',
                'handle'      => 'LOLO',
            ],
            'url' => '/account/config',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id'          => '10000000000000',
                'brand_color' => '#00BCD4',
                'handle'      => 'LOLO',
            ]
        ]
    ],

    'testEditMerchantInvalidBrandColor' => [
        'request' => [
            'content' => [
                'brand_color' => '#00bcd4',
            ],
            'url' => '/account/config',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantInvalidAutoRefundDelay' => [
        'request' => [
            'content' => [
                'auto_refund_delay' => '40 days',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Auto refund delay should be between 1 and 10 days',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantInvalidDurationAutoRefundDelay' => [
        'request' => [
            'content' => [
                'auto_refund_delay' => '3 weeeks',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Auto refund delay should be in mins, hours or days',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantAutoRefundDelay' => [
        'request' => [
            'content' => [
                'auto_refund_delay' => '3 hours',
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'auto_refund_delay' => 10800
            ],
            'status_code' => 200,
        ]
    ],

    'testStoreImageAndGetLogoUrl' => [
        'request' => [
            'content' => [],
            'url' => '/account/config/logo',
            'method' => 'post',
            'files' => [

            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
            ]
        ]
    ],

    'testDeleteLogoUrl' => [
        'request' => [
            'content' => [],
            'url' => '/account/config/logo',
            'method' => 'delete',
            'files' => [

            ],
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
            ]
        ]
    ],

    'testEditMerchantConfigWithEmail' => [
        'request' => [
            'content' => [
                'transaction_report_email' => [
                    'nemo@razorpay.com',
                    'hello@razorpay.com'
                ]
            ],
            'url' => '/account/config',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                'transaction_report_email'  => [
                    'nemo@razorpay.com',
                    'hello@razorpay.com'
                ]
            ]
        ]
    ],

    'testAttemptPaymentOnNonLiveMerchant' => [
        'request' => [
            'content' => [
                'amount' => '500',
                'currency' => 'INR',
                'email' => 'a@b.com',
                'contact' => '8383883838',
                'method' => 'netbanking',
                'bank' => 'HDFC',
            ],
            'url' => '/payments',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_LIVE_ACTION_DENIED,
        ],
    ],

    'testActivateMerchantWithoutBankAccount' => [
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
            'class' => RZP\Exception\BadRequestException::class,
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
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city' => 'Kolkata',
                'beneficiary_state' => 'WB',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '123456',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testAddBankAccountWithMerchantDetail' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020000304030434',
                'beneficiary_name' => 'Test R4zorpay',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city' => 'Kolkata',
                'beneficiary_state' => 'WB',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '123456',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testChangeBankAccountWithZeroes' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '2020000304030434',
                'beneficiary_name'      => 'Test R4zorpay',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '2020000304030434',
                'beneficiary_name' => 'Test R4zorpay',
                'beneficiary_address1' => 'address 1',
                'beneficiary_address2' => 'address 2',
                'beneficiary_address3' => 'address 3',
                'beneficiary_city' => 'Kolkata',
                'beneficiary_state' => 'WB',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '123456',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testAddBankAccountWithInvalidIFSC' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'IIC0001206',
                'account_number'        => '0002020000304030434',
                'beneficiary_name'      => 'Test R4zorpay',
                'beneficiary_address1'  => 'address 1',
                'beneficiary_address2'  => 'address 2',
                'beneficiary_address3'  => 'address 3',
                'beneficiary_address4'  => 'address 4',
                'beneficiary_email'     => 'random@email.com',
                'beneficiary_mobile'    => '9988776655',
                'beneficiary_city'      => 'Kolkata',
                'beneficiary_state'     => 'WB',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '123456',
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST'
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
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetBankAccount' => [
        'request' => [
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'RZPB0000000',
                'account_number' => '10010101011',
                'beneficiary_name' => 'random_name',
                'beneficiary_address1' => 'address1',
                'beneficiary_address2' => 'address2',
                'beneficiary_address3' => 'address3',
                'beneficiary_address4' => 'address4',
                'beneficiary_email' => 'random@email.com',
                'beneficiary_mobile' => '9988776655',
            ]
        ]
    ],

    'testChangeBankAccount' => [
        'request' => [
            'content' => [
                'ifsc_code'             => 'ICIC0001206',
                'account_number'        => '0002020005304612497',
                'beneficiary_name'      => 'Test R4zorpay',
                'beneficiary_address1'  => '4ddr3ss 1',
                'beneficiary_address2'  => '4ddr3ss 2',
                'beneficiary_address3'  => '4ddr3ss 3',
                'beneficiary_address4'  => '4ddr3ss 4',
                'beneficiary_email'     => 'r4nd0m@email.com',
                'beneficiary_mobile'    => '9876543210',
                'beneficiary_city'      => 'Mumbai',
                'beneficiary_state'     => 'MH',
                'beneficiary_country'   => 'IN',
                'beneficiary_pin'       => '567890',
            ],
            'url' => '/merchants/10000000000000/bank_account',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'ifsc_code' => 'ICIC0001206',
                'account_number' => '0002020005304612497',
                'beneficiary_name' => 'Test R4zorpay',
                'beneficiary_address1' => '4ddr3ss 1',
                'beneficiary_address2' => '4ddr3ss 2',
                'beneficiary_address3' => '4ddr3ss 3',
                'beneficiary_city' => 'Mumbai',
                'beneficiary_state' => 'MH',
                'beneficiary_country' => 'IN',
                'beneficiary_pin' => '567890',
                'beneficiary_email' => 'r4nd0m@email.com',
                'beneficiary_mobile' => '9876543210',
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
                    'ICIC'
                ]
            ]
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank',
                    'ICIC' => 'ICICI Bank',
                ],
                'disabled' => [],
            ],
        ]
    ],

    'testSetEmptyBanks' => [
        'request' => [
            'url' => '/merchants/10000000000000/banks',
            'method' => 'POST',
            'content' => [
                'banks' => []
            ]
        ],
        'response' => [
            'content' => [
                'enabled' => [],
                'disabled' => [
                    'HDFC' => 'HDFC Bank',
                    'ICIC' => 'ICICI Bank',
                ],
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

    'testGetPaymentMethodsRoute' => [
        'request' => [
            'url' => '/methods',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'methods',
                'card' => true,
                'netbanking' => [
                    'UTIB' => 'Axis Bank',
//                    'BARB' => 'Bank of Baroda',
                    'YESB' => 'Yes Bank',
                ],
                'wallet' => [
                    'paytm' => true,
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDisabled' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesForMerchantDisabledBanks' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesForTpvEnabledMerchant' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNonOrderRelatedOffer' => [
        'request' => [
            'url'    => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name'            => 'Test Offer',
                        'payment_method'  => 'wallet',
                        'issuer'          => 'olamoney',
                        'display_text'    => 'Some display text',
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithOrderRelatedOffer' => [
        'request' => [
            'url'    => null,
            'method' => 'get',
        ],
        'tests' => [
            [
                'offer' => [
                    'payment_method'      => 'card',
                    'payment_method_type' => 'credit',
                    'payment_network'     => 'VISA',
                    'issuer'              => 'HDFC',
                    'iins'                => ['123456'],
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity' => 'methods',
                            'card'   => true
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'card',
                                'payment_network' => 'VISA',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'offer' => [
                    'payment_method'      => 'netbanking',
                    'payment_network'     => 'HDFC',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity'     => 'methods',
                            'netbanking' => [
                                'HDFC' => 'HDFC Bank',
                            ]
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'netbanking',
                                'payment_network' => 'HDFC',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'offer' => [
                    'issuer'              => 'HDFC',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'issuer'          => 'HDFC',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
            [
                'offer' => [
                    'payment_method'      => 'wallet',
                    'issuer'              => 'airtelmoney',
                    'error_message'       => 'Payment method used is not eligible for offer. Please try with a different payment method.',
                    'display_text'        => 'Some display text',
                    'terms'               => 'Some terms',
                ],
                'response' => [
                    'content' => [
                        'methods' => [
                            'entity'     => 'methods',
                            'wallet' => [
                                'airtelmoney'
                            ]
                        ],
                        'offers' => [
                            [
                                'name'            => 'Test Offer',
                                'payment_method'  => 'wallet',
                                'issuer'          => 'airtelmoney',
                                'display_text'    => 'Some display text',
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithAllCardGeatewayDowntime' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'card' => [
                        [
                            'issuer'    => ['ALL'],
                            'scheduled' => true,
                            'severity'  => 'low',
                            'card_type' => 'credit',
                            'network'   => ['VISA'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoForDirectNetbankingGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer'    => 'HDFC'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithSharedNetbankingGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 30,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ALLA',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BBKM',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BKDN',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'COSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DEUT',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DBSS',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'IDFB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'IBKL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'JSBP',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'KVBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'NKGS',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'PMCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBBJ',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBHY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBIN',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBMY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'STBP',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBTR',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SIBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SVCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SYNB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TMBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TNSC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BARB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BARB_R',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'PUNB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'LAVB_C',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithBothSharedAndDirectGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 31,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer'    => 'HDFC'
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'ALLA',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BBKM',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BKDN',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'COSB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DEUT',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'DBSS',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'IDFB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'IBKL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'JSBP',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'KVBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'NKGS',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'PMCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBBJ',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBHY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBIN',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBMY',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'STBP',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SBTR',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SCBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SIBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SVCB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'SYNB',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TMBL',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'TNSC',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BARB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'BARB_R',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'PUNB_C',
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer' => 'LAVB_C',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeWithNoBanksExclusiveToGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => [],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithIssuerExclusiveToGateway' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer'    => 'ALLA'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithIssuerNA' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 0,
                'items' => [
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithGatewayAll' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'low',
                        'instrument' => [
                            'issuer'    => 'HDFC'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetNetbankingDowntimeInfoWithMultipleDowntimes' => [
        'request' => [
            'url' => '/methods/downtime',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'method' => 'netbanking',
                        'severity' => 'medium',
                        'instrument' => [
                            'issuer' => 'ALLA'
                        ],
                    ],
                    [
                        'method' => 'netbanking',
                        'severity' => 'high',
                        'instrument' => [
                            'issuer' => 'HDFC'
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCardDowntimeWithIssuerOrNetworkUnknown' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCardDowntimeWithSpecificGatewayDown' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'card' => [
                        [
                            'issuer'    => ['ALL'],
                            'scheduled' => true,
                            'severity'  => 'low',
                            'card_type' => 'credit',
                            'network'   => ['DICL'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithCardDowntimeWithGatewayExclusiveNetworkDown' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'card' => [
                        [
                            'issuer'    => ['ALL'],
                            'scheduled' => true,
                            'severity'  => 'low',
                            'card_type' => 'credit',
                            'network'   => ['DICL'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDowntimeWithAllGateway' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer'    => ['HDFC'],
                            'scheduled' => true,
                            'severity'  => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDowntimeWithSharedNetbankingGateway' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer'      => [
                                'ALLA',
                                'BBKM',
                                'BKDN',
                                'COSB',
                                'DCBL',
                                'DCBL',
                                'DEUT',
                                'DBSS',
                                'IDFB',
                                'IBKL',
                                'JSBP',
                                'KVBL',
                                'NKGS',
                                'PMCB',
                                'SBBJ',
                                'SBHY',
                                'SBIN',
                                'SBMY',
                                'STBP',
                                'SBTR',
                                'SCBL',
                                'SIBL',
                                'SVCB',
                                'SYNB',
                                'TMBL',
                                'TNSC',
                                'BARB_C',
                                'BARB_R',
                                'PUNB_C',
                                'LAVB_C'
                            ],
                            'scheduled'   => true,
                            'severity'    => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingWithIssuerExclusiveTogateway' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer'    => ['ALLA'],
                            'scheduled' => true,
                            'severity'  => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithDirectNetbankingDowntime' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer'    => ['HDFC'],
                            'scheduled' => true,
                            'severity'  => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithWalletDowntime' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'wallet' => [
                        [
                            'issuer'    => ['olamoney'],
                            'scheduled' => true,
                            'severity'  => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testGetCheckoutPreferencesWithNetbankingDowntimeWithDirectTerminal' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'downtime' => [
                    'netbanking' => [
                        [
                            'issuer' => [
                                'ALLA',
                                'BBKM',
                                'BKDN',
                                'COSB',
                                'DCBL',
                                'DCBL',
                                'DEUT',
                                'DBSS',
                                'IDFB',
                                'IBKL',
                                'JSBP',
                                'KVBL',
                                'NKGS',
                                'PMCB',
                                'RATN',
                                'SBBJ',
                                'SBHY',
                                'SBIN',
                                'SBMY',
                                'STBP',
                                'SBTR',
                                'SCBL',
                                'SIBL',
                                'SVCB',
                                'SYNB',
                                'TMBL',
                                'TNSC',
                                'BARBC',
                                'BARBR',
                                'PUNBC',
                                'LAVBC'
                            ],
                            'scheduled'   => true,
                            'severity'    => 'low',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testPutPaytmMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'paytm' => true
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                //''
            ]
        ]
    ],

    'testPutEmiMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'emi' => true,
            ],
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testGetKeySecret' => [
        'request' => [
            'url' => '/keys/rzp_test_TheTestAuthKey/secret',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'secret' => 'TheKeySecretForTests'
            ]
        ]
    ],

    'testEditCreditsWrongFormat' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetCheckoutRouteWithEmi' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithSavedGlobal' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'app_token' => 'capp_1000000custapp'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithSavedLocal' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'customer_id' => 'cust_100000customer'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteCustomerContact' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithDeviceToken' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'device_token' => '1000custdevice'
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'saved' => true,
                    'contact' => '9988776655',
                    'email' => 'test@razorpay.com',
                ]
            ],
        ],
    ],

    'testGetCheckoutRouteWithAndroidMetadata' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'device_token' => '1000custdevice',
                '_' => [
                    'library' => 'checkoutjs',
                    'platform' => 'android',
                    'version' => '1.0.0',
                ]
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetCheckoutRouteWithAndroidMetadataNoSession' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
            'content' => [
                'contact' => '9988776655',
                'device_token' => '1000custdevice',
                '_' => [
                    'library' => 'checkoutjs',
                    'platform' => 'android',
                    'version' => '1.0.0',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'customer' => [
                    'saved' => true,
                    'contact' => '9988776655',
                    'email' => 'test@razorpay.com',
                ]

            ],
        ],
    ],

    'testAddCategory2' => [
        'request' => [
            'content' => [
                'category2' => 'govt_education'
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testAddInvalidCategory2' => [
        'request' => [
            'content' => [
                'category2' => 'education2'
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Category : education2 invalid for merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateImage' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_IMAGE,
        ],
    ],

    'testValidateLogoImageSmall' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_SMALL,
        ],
    ],

    'testValidateLogoImageNotSquare' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_NOT_SQUARE,
        ],
    ],

    'testValidateLogoImageTooBig' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_LOGO_TOO_BIG,
        ],
    ],

    'testEditMerchantEditGroups' => [
        'request' => [
            'url'       => '/merchants/%s',
            'method'    => 'put',
            'content'   => []
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testGetMerchantFeatures' => [
        'request' => [
            'url' => '/merchants/10000000000000/features',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => false,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'marketplace',
                        'value'        => false,
                        'display_name' => 'Route'
                    ],
                    [
                        'feature'      => 'subscriptions',
                        'value'        => false,
                        'display_name' => 'Subscriptions'
                    ],
                    [
                        'feature'      => 'virtual_accounts',
                        'value'        => false,
                        'display_name' => 'Smart Collect'
                    ],
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testUpdateMerchantFeatures' => [
        'request' => [
            'content' => [
                'features' => [
                    'noflashcheckout' => '1',
                ],
                'optout_reason' => 'some reason'
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature' => 'noflashcheckout',
                        'value' => true,
                        'display_name' => 'No Flash Checkout'
                    ]
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testUpdateMerchantUnEditableFeatures' => [
        'request' => [
            'content' => [
                'features' => [
                    'dummy' => '1'
                ]
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
        ],
    ],

    'testAddMerchantUnEditableFeaturesOnLive' => [
        'request' => [
            'content' => [
                'features' => [
                    'marketplace' => '1'
                ]
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
        ],
    ],

    'testAddMerchantEditableFeaturesOnTest' => [
        'request' => [
            'content' => [
                'features' => [
                    'marketplace' => '1',
                ]
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => false,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'marketplace',
                        'value'        => true,
                        'display_name' => 'Route'
                    ],
                    [
                        'feature'      => 'subscriptions',
                        'value'        => false,
                        'display_name' => 'Subscriptions'
                    ],
                    [
                        'feature'      => 'virtual_accounts',
                        'value'        => false,
                        'display_name' => 'Smart Collect'
                    ],
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testAddMerchantFeaturesWithSyncOnTest' => [
        'request' => [
            'content' => [
                'features'      => [
                    'noflashcheckout' => '1',
                ],
                'should_sync'   => 1
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => true,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'marketplace',
                        'value'        => false,
                        'display_name' => 'Route'
                    ],
                    [
                        'feature'      => 'subscriptions',
                        'value'        => false,
                        'display_name' => 'Subscriptions'
                    ],
                    [
                        'feature'      => 'virtual_accounts',
                        'value'        => false,
                        'display_name' => 'Smart Collect'
                    ],
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testAddMerchantFeaturesWithSyncOnLive' => [
        'request'  => [
            'content' => [
                'features'    => [
                    'noflashcheckout' => '1',
                ],
                'should_sync' => 1
            ],
            'url'     => '/merchants/10000000000000/features',
            'method'  => 'post',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content'     => [
                'features' => [
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => true,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'marketplace',
                        'value'        => false,
                        'display_name' => 'Route'
                    ],
                    [
                        'feature'      => 'subscriptions',
                        'value'        => false,
                        'display_name' => 'Subscriptions'
                    ],
                    [
                        'feature'      => 'virtual_accounts',
                        'value'        => false,
                        'display_name' => 'Smart Collect'
                    ]
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testAddMerchantUneditableFeaturesWithSyncOnLive' => [
        'request' => [
            'content' => [
                'features'      => [
                    'subscriptions' => '1',
                ],
                'should_sync'   => 1
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
        ],
    ],

    'testAddMerchantEditableFeaturesWithSyncOnTest' => [
        'request' => [
            'content' => [
                'features'      => [
                    'subscriptions' => '1',
                ],
                'should_sync'   => 1
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
        ],
    ],

    'testDeleteMerchantUnEditableFeatureFromLive' => [
        'request' => [
            'content' => [
                'features' => [
                    'marketplace' => '0'
                ]
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
        ],
    ],

    'testDeleteMerchantEditableFeatureFromTest' => [
        'request' => [
            'content' => [
                'features' => [
                    'marketplace' => '0',
                ],
                'should_sync'   => 1
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
        ],
    ],

    'testMerchantArchive' => [
        'request' => [
            'content' => [
                'action' => 'archive'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
            ]
        ]
    ],

    'testMerchantArchiveWithNoMerchantDetails' => [
        'request' => [
            'content' => [
                'action' => 'archive'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_DOES_NOT_EXISTS,
        ],
    ],

    'testMerchantArchiveForAlreadyArchivedMerchant' => [
        'request' => [
            'content' => [
                'action' => 'archive'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_ARCHIVED,
        ],
    ],

    'testMerchantUnarchive' => [
        'request' => [
            'content' => [
                'action' => 'unarchive'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
            ]
        ]
    ],

    'testMerchantUnarchiveForNonArchived' => [
        'request' => [
            'content' => [
                'action' => 'unarchive'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ARCHIVED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ARCHIVED,
        ],
    ],

    'testMerchantSuspend' => [
        'request' => [
            'content' => [
                'action' => 'suspend'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
                'live' => false,
                'hold_funds' => true,
            ]
        ]
    ],

    'testMerchantSuspendForAlreadySuspendedMerchant' => [
        'request' => [
            'content' => [
                'action' => 'suspend'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ALREADY_SUSPENDED,
        ],
    ],

    'testMerchantUnSuspend' => [
        'request' => [
            'content' => [
                'action' => 'unsuspend'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'entity' => 'merchant',
                'activated' => false,
                'live' => true,
                'hold_funds' => false,
            ]
        ]
    ],

    'testMerchantUnSuspendForAlreadyUnSuspendedMerchant' => [
        'request' => [
            'content' => [
                'action' => 'unsuspend'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_SUSPENDED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_NOT_SUSPENDED,
        ],
    ],

    'testMerchantUndefinedAction' => [
        'request' => [
            'content' => [
                'action' => 'hello123'
            ],
            'url' => '/merchants/1cXSLlUU8V9sXl/action',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
        ],
    ],

    'testScheduleTaskMigration' => [
        'request' => [
            'url' => '/merchants/schedules/migrate',
            'method' => 'POST',
            'content' => [
                'merchant_ids' => [
                    '1X4hRFHFx4UiXt'
                ]
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'verifyFeaturePresence' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'assigned_features' => [
                    [
                        'name'        => 'noflashcheckout',
                        'entity_id'   => '10000000000000',
                        'entity_type' => 'merchant'
                    ]
                ],
                'all_features'      => [
                    'dummy',
                    'webhooks',
                    'aggregator',
                    'tokens',
                    's2swallet',
                    's2supi',
                    's2saeps',
                    'noflashcheckout',
                    'recurring',
                    's2s',
                    'invoice',
                    'nozeropricing',
                    'reverse',
                ]
            ]
        ]
    ],
    'testUpdateSubmerchantEmail' => [
        'request' => [
            'url'       => '/merchants/10000000000044/email',
            'method'    => 'PUT',
            'content'   => [
                'email' => 'differentemail@razorpay.com'
            ],
            'server' => [
                'HTTP_' . \RZP\Http\BasicAuth\BasicAuth::ACCOUNT_HEADER_KEY => '10000000000044',
            ],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_MERCHANT_EMAIL_SAME_AS_PARENT_EMAIL,
        ],
    ]
];
