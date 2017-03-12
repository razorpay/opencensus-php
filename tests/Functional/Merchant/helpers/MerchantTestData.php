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
            'class' => 'RZP\Exception\BadRequestException',
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
                    'banks' => [],
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_KEY_OF_DEMO_ACCOUNT,
        ],
    ],

    'testEditMerchant' => [
        'request' => [
            'content' => [
                'international' => '1',
                'website' => 'http://abc.com',
                'category' => '1111',
                'transaction_report_email'  => [
                    'test@razorpay.com'
                ]
            ],
            'url' => '/merchants/1X4hRFHFx4UiXt',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'id' => '1X4hRFHFx4UiXt',
                'entity' => 'merchant',
                'international' => true,
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditMerchantConfig' => [
        'request' => [
            'content' => [
                'brand_color' => '00bcd4',
            ],
            'url' => '/account/config',
            'method' => 'put',
        ],
        'response' => [
            'content' => [
                'id' => '10000000000000',
                'brand_color' => '#00BCD4'
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
                    'description' => 'Auto refund delay should be between 1 and 5 days',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
                    'ICIC',
                ]
            ]
        ],
        'response' => [
            'content' => [
                'enabled' => [
                    'HDFC' => 'HDFC Bank Ltd',
                    'ICIC' => 'ICICI Bank Ltd',
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
                    'HDFC' => 'HDFC Bank Ltd',
                    'ICIC' => 'ICICI Bank Ltd',
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
                'HDFC' => 'HDFC Bank Ltd',
                'ICIC' => 'ICICI Bank Ltd',
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
                    'HDFC' => 'HDFC Bank Ltd',
                    'ICIC' => 'ICICI Bank Ltd',
                ],
                'disabled' => [
                    'YESB' => 'Yes Bank Ltd',
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
                    'YESB' => 'Yes Bank Ltd',
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

    'testGetCheckoutPreferencesWithOffer' => [
        'request' => [
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPutPaytmMethod' => [
        'request' => [
            'url' => '/merchants/10000000000000/methods',
            'method' => 'put',
            'content' => [
                'paytm' => true,
                'banks' => [
                    'UTIB',
                    'PUNB',
                ],
            ]
        ],
        'response' => [
            'content' => [
                //''
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
                        'feature' => "noflashcheckout",
                        'value' => FALSE,
                        'display_name' => "No Flash Checkout"
                    ],
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testUpdateMerchantFeatures' => [
        'request' => [
            'content' => [
                "features" => [
                    "noflashcheckout" => "1",
                ],
                "optout_reason" => "some reason"
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature' => "noflashcheckout",
                        'value' => TRUE,
                        'display_name' => "No Flash Checkout"
                    ]
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testUpdateMerchantUnEditableFeatures' => [
        'request' => [
            'content' => [
                "features" => [
                    "dummy" => "1"
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
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
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
        ],
    ],

    'testMerchantScheduleMigration' => [
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
    ]
];
