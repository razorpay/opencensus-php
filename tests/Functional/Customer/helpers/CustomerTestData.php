<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateCustomer' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'customer',
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
    ],

    'testCreateCustomerWithValidNames' => [
        'request' => [
            'url'     => '/customers',
            'method'  => 'post',
            'content' => [
                'name'    => 'testc',             // Replaced with different valid names in tests
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
                'gstin'   => '29ABCDE1234L1Z1',
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'customer',
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
                'gstin'   => '29ABCDE1234L1Z1',
            ],
        ],
    ],

    'testCreateCustomerWithNameNull' => [
        'request' => [
            'url'     => '/customers',
            'method'  => 'post',
            'content' => [
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'customer',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
                'name'    => null,
            ],
        ],
    ],

    'testCreateCustomerWithLeadingOrTrailingSpaces' => [
        'request' => [
            'url'     => '/customers',
            'method'  => 'post',
            'content' => [
                'name'    => 'testc',             // Replaced with different valid names in tests
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'entity'  => 'customer',
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
    ],

    'testCreateCustomerWithInvalidNames' => [
        'request' => [
            'url'     => '/customers',
            'method'  => 'post',
            'content' => [
                'name'    => 'testc',             // Replaced with different invalid names in tests
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The name format is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateCustomerEmailOnly' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
            ],
        ],
    ],

    'testCreateCustomerUppercaseEmailOnly' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'UPPERCASE@Razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'email'   => 'uppercase@razorpay.com',
            ],
        ],
    ],

    'testCreateCustomerContactOnly' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'contact' => '1234567888',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'contact'   => '1234567888',
            ],
        ],
    ],

    'testCreateCustomerDuplicatePhone' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'test11@razorpay.com',
                'contact' => '9988776655'
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'email'   => 'test11@razorpay.com',
                'contact' => '9988776655'
            ],
        ],
    ],

    'testCreateCustomerDuplicateEmail' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567888'
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567888'
            ],
        ],
    ],

    'testCreateCustomerDuplicate' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567890'
            ],
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_CUSTOMER_ALREADY_EXISTS,
        ],
    ],

    'testCreateCustomerDuplicateDontFail' => [
        'request' => [
            'url' => '/customers',
            'method' => 'post',
            'content' => [
                'name' => 'testc',
                'email' => 'test@razorpay.com',
                'contact' => '1234567890',
                'fail_existing' => '0',
            ],
        ],
        'response' => [
            'content' => [
                'id' => 'cust_100000customer',
                'name' => 'test',
                'contact' => '1234567890',
                'email' => 'test@razorpay.com'
            ]
        ]
    ],

    'testUpdateCustomer' => [
        'request' => [
            'url' => '/customers/cust_100000customer',
            'method' => 'put',
            'content' => [
                'name'    => 'test1',
                'contact' => '1234567809',
                'email'   => 'test@rzp.com',
                'gstin'   => '29CFZPR4093Q1ZA',
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'test1',
                'contact' => '1234567809',
                'email'   => 'test@rzp.com',
                'gstin'   => '29CFZPR4093Q1ZA',
            ],
        ],
    ],

    'testUpdateGlobalCustomerWithValidEmail' => [
        'request' => [
            'content' => [
                'email'   => 'test@rzp.com'
            ],
        ],
    ],

    'testUpdateGlobalCustomerWithInvalidEmail' => [
        'request' => [
            'content' => [
                'email'   => 'invalid_email_format'
            ],
        ],
    ],

    'testUpdateGlobalCustomerWithInvalidInput' => [
        'request' => [
            'content' => [
                'other'   => 'malicious code'
            ],
        ],
    ],

    'testUpdateGlobalCustomerWithInvalidSession' => [
        'request' => [
            'content' => [
                'email'   => 'test@rzp.com'
            ],
        ],
    ],

    'testUpdateCustomerEmail' => [
        'request' => [
            'url' => '/customers/cust_100000customer',
            'method' => 'put',
            'content' => [
                'email'   => 'test@rzp.com'
            ],
        ],
        'response' => [
            'content' => [
                'email'   => 'test@rzp.com'
            ],
        ],
    ],

    'testCreateCustomerInvalidGstin' => [
        'request' => [
            'url'     => '/customers',
            'method'  => 'post',
            'content' => [
                'name'    => 'testc',             // Replaced with different invalid names in tests
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
                'gstin'   => '00ABCDE1234L1Z1',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The gstin field is invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateCustomerName' => [
        'request' => [
            'url' => '/customers/cust_100000customer',
            'method' => 'put',
            'content' => [
                'name'    => 'test1'
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'test1',
                'contact' => '1234567890',
            ],
        ],
    ],

    'testGetCustomer' => [
        'request' => [
            'url' => '/customers/cust_100000customer',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'      => 'cust_100000customer',
                'name'    => 'test',
                'contact' => '1234567890',
            ],
        ],
    ],

    'testGetMultipleCustomersViaEs' => [
        'request' => [
            'url'     => '/customers',
            'method'  => 'get',
            'content' => [
                'q'           => 'name',
                'search_hits' => '1',
            ],
        ],
        'response' => [
            'content' => [
                'count' => 1,
                'items' => [
                    [
                        'id'      => 'cust_100001customer',
                        'entity'  => 'customer',
                        'name'    => 'name',
                        'contact' => '9988776655',
                        'email'   => null,
                        'gstin'   => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetMultipleCustomersViaEsExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'customer_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'customer_test',
        'body'  => [
            '_source' => true,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'multi_match' => [
                                'query'  => 'name',
                                'type'   => 'best_fields',
                                'fields' => [
                                    'name',
                                    'contact',
                                    'email',
                                    'gstin',
                                ],
                                'boost'                => 1,
                                'minimum_should_match' => '75%',
                                'lenient'              => true
                            ],
                        ],
                    ],
                    'filter' => [
                        'bool' => [
                            'must' => [
                                [
                                    'term' => [
                                        'merchant_id' => [
                                            'value' => '10000000000000',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testGetMultipleCustomersViaEsExpectedSearchResponse' => [
        'hits' => [
            'hits' => [
                [
                    '_id'     => '100001customer',
                    '_source' => [
                        'id'      => '100001customer',
                        'name'    => 'name',
                        'contact' => '9988776655',
                        'email'   => null,
                        'gstin'   => null,
                    ],
                ],
            ],
        ],
    ],

    'testGetCustomerTokens' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'token'         => '10001emantoken',
                        'method'        => 'emandate',
                        'bank'          => 'HDFC',
                        'max_amount'    =>  105,
                    ],
                    [
                        'token'         => '10001cardtoken',
                        'method'        => 'card',
                        'card'          => [
                            'last4'         => '1111',
                            'network'       => 'Visa',
                        ]
                    ],
                    [
                        'token'         => '10002cardtoken',
                        'method'        => 'card',
                        'card'          => [
                            'last4'         => '1111',
                            'network'       => 'RuPay',
                        ]
                    ],
                    [
                        'token'         => '10000cardtoken',
                        'method'        => 'card',
                        'card'          => [
                            'last4'         => '1111',
                            'network'       => 'Visa',
                        ]
                    ],
                    [
                        'token'         => '10000banktoken',
                        'method'        => 'netbanking',
                        'bank'          => 'HDFC',
                    ],
                    [
                        'token'         => '100wallettoken',
                        'method'        => 'wallet',
                        'wallet'        => 'paytm',
                    ],
                ]
            ],
        ],
    ],

    'testGetCustomerToken' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens/100wallettoken',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'token'         => '100wallettoken',
                'method'        => 'wallet',
                'wallet'        => 'paytm',
            ],
        ],
    ],

    'testUpdateCustomerToken' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens/token_1000custwallet',
            'method' => 'put',
            'content' => [
                'recurring' => 0
            ],
        ],
        'response' => [
            'content' => [
                'id'     => "token_1000custwallet",
                'entity' => "token",
                'wallet' => "paytm",
                'method' => "wallet",
            ],
        ],
    ],

    'testDeleteCustomerToken' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens/100wallettoken',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDeleteCustomerTokenById' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens/token_1000custwallet',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAddCustomerTokenCard' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens',
            'method' => 'post',
            'content' => [
                'method'  => 'card',
                'card'    => [
                    'number'       => '4012001038443335',
                    'expiry_month' => '11',
                    'expiry_year'  => '2030',
                    'name'         => 'Random',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'card',
                'card'   => [
                    'last4'   => '3335',
                    'network' => 'Visa',
                ],
                'wallet' => null,
                'bank'   => null,
            ],
        ],
    ],

    'testAddCustomerTokenWallet' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens',
            'method' => 'post',
            'content' => [
                'method' => 'wallet',
                'wallet' => 'mobikwik',
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'wallet',
                'wallet' => 'mobikwik',
                'bank' => null,
            ],
        ],
    ],

    'testAddCustomerTokenNetbanking' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens',
            'method' => 'post',
            'content' => [
                'method'        => 'netbanking',
                'bank'          => 'KKBK',
                'max_amount'    => 10000000,
            ],
        ],
        'response' => [
            'content' => [
                'method'        => 'netbanking',
                'wallet'        => null,
                'bank'          => 'KKBK',
            ],
        ],
    ],

    'testFetchSavedTokensStatusSaved'   => [
        'request' => [
                'url' => '/customers/status/9988776655',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => true,
                ],
            ],
    ],

    'testFetchTokenByCustomerIdWhenStatusIsActive'   => [
        'request' => [
            'url' => '/customers/cust_1000ggcustomer/tokens/token_100022xytoken1',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'token_100022xytoken1',
                'method'        => 'card',
                'status'        => 'active',
                'error_code'    => null,
                'error_description' => null
            ],
        ]
    ],

    'testFetchTokenByCustomerIdWhenStatusIsFailed'   => [
        'request' => [
            'url' => '/customers/cust_1000ggcustomer/tokens/token_100022xytoken1',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'token_100022xytoken1',
                'method'        => 'card',
                'status'        => 'failed',
                'error_code'    =>  'BAD_REQUEST_ERROR',
                'error_description' => 'The card is not eligible for tokenisation.'
            ],
        ]
    ],

    'testFetchTokenByCustomerIdWhenStatusIsEmpty'   => [
        'request' => [
            'url' => '/customers/cust_1000ggcustomer/tokens/token_100022xytoken1',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'id'            => 'token_100022xytoken1',
                'method'        => 'card',
                'status'        => 'failed',
                'error_code'    =>  'BAD_REQUEST_ERROR',
                'error_description' => 'Token creation failed'
            ],
        ]
    ],

    'testFetchSavedTokensStatusWhenNoCustomerTokensArePresentOnMerchantExpectsOtpGettingSkipped' => [
        'request' => [
                'url' => '/customers/status/9988776655',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => false,
                ],
            ],
    ],

    'testFetchSavedTokensStatusWhenCustomerDoesNotExistsExpectsOtpGettingSkipped' => [
        'request' => [
                'url' => '/customers/status/9988776656',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => false,
                ],
            ],
    ],

    'testFetchSavedTokensStatusWhenCustomerTokensArePresentOnDifferentMerchantExpectsOtpGettingSkipped' => [
        'request' => [
                'url' => '/customers/status/9988776655',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => false,
                ],
            ],
    ],

    'testCustomerStatusApiWhenSavedCardTokensNotPresentExpectsOtpGettingSkipped' => [
        'request' => [
            'url' => '/customers/status/9988776655',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'saved' => false,
            ],
        ],
    ],

    'testFetchSavedTokensStatusWhenInvalidCustomerTokensArePresentExpectsOtpGettingSkipped' => [
        'request' => [
                'url' => '/customers/status/9988776655',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => false,
                ],
            ],
    ],

    'testFetchSavedTokensStatusSavedSkipOTPSend'   => [
        'request' => [
                'url' => '/customers/status/9988776655',
                'method' => 'get',
                'content' => [
                    'skip_otp' => true
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => true,
                ],
            ],
    ],

    'testFetchSavedCustomerStatusWithDeviceToken'   => [
        'request' => [
                'url' => '/customers/status/9988776655',
                'method' => 'get',
                'content' => [
                    'device_token' => '1000custdevice'
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => true,
                    'email' => 'test@razorpay.com',
                    'tokens' => [
                        'entity' => 'collection',
                        'count'  => 1,
                        'items'  => [],
                    ]
                ],
            ],
    ],

    'testFetchSavedTokensStatusNotSaved'   => [
        'request' => [
                'url' => '/customers/status/1234567899',
                'method' => 'get',
                'content' => [
                ],
            ],
            'response' => [
                'content' => [
                    'saved' => false
                ],
            ],
    ],

    'testVerifyDeviceToken'   => [
        'request' => [
                'url' => '/devices/1000custdevice/verify',
                'method' => 'post',
                'content' => [
                    'contact' => '9988776655',
                ],
            ],
            'response' => [
                'content' => [
                    'valid' => true
                ],
            ],
    ],

    'testDeleteAppToken' => [
        'request' => [
            'url' => '/apps/tokens/1000gcardtoken',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testLogoutFromApp' => [
        'request' => [
            'url' => '/apps/logout',
            'method' => 'delete',
            'content' => [
                'logout' => 'app',
                'app_token' => 'capp_1000000custapp',
                'device_token' => '1000custdevice'
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testLogoutFromDevice' => [
        'request' => [
            'url' => '/apps/logout',
            'method' => 'delete',
            'content' => [
                'logout' => 'device',
                'device_token' => '1000custdevice'
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testLogoutFromAllDevices' => [
        'request' => [
            'url' => '/apps/logout',
            'method' => 'delete',
            'content' => [
                'logout' => 'all'
            ],
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'testOtpFlowWithInvalidNumber' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact field is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOtpWorkFlowWithEmailRequired' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                 ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCustomerWalletPayoutInsufficientWalletBalance' => [
        'request'   => [
            'url'     => '/customers/cust_100000customer/payouts',
            'method'  => 'post',
            'content' => [
                'amount'          => 300,
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',

                'currency' => 'INR',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payout failed due to insufficient balance in wallet',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_WALLET_PAYOUT_INSUFFICIENT_BALANCE,
        ],
    ],

    'testCustomerWalletPayoutInsufficientMerchantBalance' => [
        'request'   => [
            'url'     => '/customers/cust_100000customer/payouts',
            'method'  => 'post',
            'content' => [
                'amount'          => 800,
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',

                'currency' => 'INR',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant does not have enough balance for negative adjustment',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INSUFFICIENT_BALANCE_FOR_ADJUSTMENT,
        ],
    ],

    'testCustomerWalletPayout' => [
        'request'  => [
            'url'     => '/customers/cust_100000customer/payouts',
            'method'  => 'post',
            'content' => [
                'amount'          => 800,
                'purpose'         => 'refund',
                'fund_account_id' => 'fa_100000000000fa',
                'currency'        => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'customer_id'     => 'cust_100000customer',
                'fund_account_id' => 'fa_100000000000fa',
                'currency'        => 'INR',
                'amount'          => 800,
                'status'          => 'processing',
            ]
        ],

    ],


    'testAddCustomerTokenCardCardVault' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens',
            'method' => 'post',
            'content' => [
                'method'  => 'card',
                'card'    => [
                    'number'       => '4012001038443335',
                    'expiry_month' => '11',
                    'expiry_year'  => '2030',
                    'name'         => 'Random',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'card',
                'card'   => [
                    'last4'   => '3335',
                    'network' => 'Visa',
                ],
                'wallet' => null,
                'bank'   => null,
            ],
        ],
    ],

    'testGetTokenWithBankDetails' => [
        'request' => [
            'url' => '/customers/cust_100000customer/tokens/10001emantoken',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'token'         => '10001emantoken',
                'method'        => 'emandate',
                'bank'          => 'HDFC',
                'bank_details'  => [
                    'beneficiary_name' => 'BeneficiaryName',
                    'account_number'   => '10000',
                    'ifsc'             => 'ifsc',
                    'account_type'     => 'account_type',
                ],
                'max_amount'       =>  105,
            ],
        ]
    ],
    'testCreateGlobalAddress' => [
        'request'   => [
            'url'   => '/customers/addresses',
            'method' => 'post',
            'content' => [
                'contact'   => '+919999999999',
                'email'     => 'test@razorpay.com',
                'shipping_address' => [
                    'name'  => 'test_customer',
                    'type'  => 'shipping_address',
                    'line1' => 'some line one',
                    'line2' => '.',
                    'zipcode' => '560078',
                    'city'  => 'Bangalore',
                    'state' => 'Karnataka',
                    'tag'   => 'home',
                    'landmark' => 'some landmark',
                    'primary' => 0,
                    'country' => 'in',
                ],
            ],
        ],
        'response'  => [
            'content' => [
                'shipping_address' => [
                    'primary' => false,
                    'type' => 'shipping_address',
                    'name'  => 'test_customer',
                    'line1' => 'some line one',
                    'line2' => '.',
                    'zipcode' => '560078',
                    'city'  => 'Bangalore',
                    'state' => 'Karnataka',
                    'tag'   => 'home',
                    'landmark' => 'some landmark',
                ],
            ],
        ],
    ],
    'testEditGlobalAddress' => [
        'request'   => [
            'url'   => '/customers/addresses',
            'method' => 'put',
            'content' => [
                'contact'   => '+919999999999',
                'email'     => 'test@razorpay.com',
                'shipping_address' => [
                    'id' => 'ABED31lJldZoJf',
                    'name'  => 'test_customer',
                    'type'  => 'shipping_address',
                    'line1' => 'some line one',
                    'line2' => 'some line 2',
                    'zipcode' => '560078',
                    'city'  => 'Bangalore',
                    'state' => 'Karnataka',
                    'tag'   => 'home',
                    'landmark' => 'some landmark',
                    'primary' => 0,
                    'country' => 'in',
                ],
            ],
        ],
        'response'  => [
            'content' => [
                'shipping_address' => [
                    'primary' => false,
                    'type' => 'shipping_address',
                    'name'  => 'test_customer',
                    'line1' => 'some line one',
                    'line2' => 'some line 2',
                    'zipcode' => '560078',
                    'city'  => 'Bangalore',
                    'state' => 'Karnataka',
                    'tag'   => 'home',
                    'landmark' => 'some landmark',
                ],
            ],
        ],
    ],

    'testFetchAppTokensV2SingleCardSingleTokenSingleMerchantSuccessful' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                "contact" => "+919988776655",
                "cards" => [
                    [
                        "last4"         => "1111",
                        "network"       => "Visa",
                        "type"          => "debit",
                        "issuer"        => "hdfc",
                        "tokens" => [
                            [
                                'id'         => '10000custgcard',
                                'created_at' => 1500000004
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testFetchAppTokensV2SingleCardMultipleTokensDifferentMerchantsSuccessful' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                "contact" => "+919988776655",
                "cards" => [
                    [
                        "last4"         => "1111",
                        "network"       => "Visa",
                        "type"          => "debit",
                        "issuer"        => "hdfc",
                        "tokens" => [
                            [
                                'id'         => '10000custgcard',
                                'created_at' => 1500000004
                            ],
                            [
                                'created_at' => 1234567890
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testFetchAppTokensV2MultipleCardsDifferentMerchantsSuccessful' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                "contact" => "+919988776655",
                "cards" => [
                    [
                        "last4"         => "1234",
                        "network"       => "Visa",
                        "type"          => "credit",
                        "issuer"        => "sbi",
                        "tokens" => [
                            []
                        ]
                    ],
                    [
                        "last4"         => "1111",
                        "network"       => "Visa",
                        "type"          => "debit",
                        "issuer"        => "hdfc",
                        "tokens" => [
                            [
                                'id'         => '10000custgcard',
                                'created_at' => 1500000004
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testFetchAppTokensV2MultipleCardsMultipleTokensMultipleMerchantsSuccessful' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                "contact" => "+919988776655",
                "cards" => [
                    [
                        "last4"         => "1234",
                        "network"       => "Visa",
                        "type"          => "credit",
                        "issuer"        => "sbi",
                        "tokens" => [
                            []
                        ]
                    ],
                    [
                        "last4"         => "1111",
                        "network"       => "Visa",
                        "type"          => "debit",
                        "issuer"        => "hdfc",
                        "tokens" => [
                            [
                                'id'         => '10000custgcard',
                                'created_at' => 1500000004
                            ],
                            [
                                'created_at' => 1234567890
                            ]
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testFetchAppTokensV2CustomerNotAuthenticated' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_AUTHENTICATED,
                ],
            ],
            'status_code' => 401,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        ],
    ],

    'testDeleteAppTokensV2Successful' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'delete',
            'content' => [
                'tokens' => []
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDeleteAppTokensV2CustomerNotAuthenticated' =>[
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'get',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_NOT_AUTHENTICATED,
                ],
            ],
            'status_code' => 401,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_NOT_AUTHENTICATED,
        ],
    ],

    'testDeleteAppTokensV2SizeValidationFailure' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'delete',
            'content' => [
                'tokens' => ['1234567890']
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The tokens.0 must be 14 characters.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteAppTokensV2TypeValidationFailure' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'delete',
            'content' => [
                'tokens' => '1234567890'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The tokens must be an array.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteAppTokensV2CountNotEqualValidationFailure' => [
        'request' => [
            'url' => '/v2/apps/tokens',
            'method' => 'delete',
            'content' => [
                'tokens' => []
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'One or more tokens do not belong to this customer',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CUSTOMER_TOKEN_COUNT_NOT_EQUAL,
        ],
    ],

    'testCreateGlobalCustomerMagicClub' => [
        'request' => [
            'url' => '/customers/1cc/global',
            'method' => 'post',
            'content' => [
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'contact' => '1234567899',
            ],
        ],
    ],

    'testGetGlobalCustomerMagicClub' => [
        'request' => [
            'url' => '/customers/1cc/global',
            'method' => 'post',
            'content' => [
                'contact' => '1234567899',
            ],
        ],
        'response' => [
            'content' => [
                'contact' => '1234567899',
            ],
        ]
    ],

    'testGetGlobalCustomerByID' => [
        'request' => [
            'url'    => '/customers/1cc/global/cust_magic1customer',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'id'      => 'cust_magic1customer',
                'contact' => '9988771111',
                'entity'  => 'customer',
                'name'    => 'name',
                'email'   => null,
            ],
        ],
    ],

    'testGetOrCreateGlobalCustomerMagicClubInvalidInput' => [
        'request' => [
            'url' => '/customers/1cc/global',
            'method' => 'post',
            'content' => [
                'contact' => '',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchCustomerTokensInternalForGlobalCustomer' => [
        'request' => [
            'url' => '/internal/customers/tokens',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'id' => 'token_KuClzOupNoxchg',
                        'entity' => 'token',
                        'token' => 'KuClzOvHeBP36p',
                        'bank' => 'ICIC',
                        'wallet' => 'paytm',
                        'method' => 'card',
                        'card' => [
                            'entity' => 'card',
                            'name' => '',
                            'last4' => '1234',
                            'network' => 'Visa',
                            'type' => 'credit',
                            'issuer' => 'HDFC',
                            'international' => false,
                            'emi' => true,
                            'sub_type' => null,
                            'token_iin' => null,
                            'expiry_month' => '01',
                            'expiry_year' => '2099',
                            'flows' => [],
                            'cobranding_partner' => null,
                        ],
                        'recurring' => false,
                        'recurring_details' => [
                            'status' => null,
                            'failure_reason' => null,
                        ],
                        'auth_type' => null,
                        'mrn' => null,
                        'used_at' => 1671548162,
                        'created_at' => 1671548162,
                        'expired_at' => 1896114599,
                        'status' => 'active',
                        'notes' => [],
                        'dcc_enabled' => false,
                        'compliant_with_tokenisation_guidelines' => true,
                    ],
                    [
                        'id' => 'token_KuClzN7vGGpga0',
                        'entity' => 'token',
                        'token' => 'KuClzN8Q5ttGR8',
                        'bank' => 'ICIC',
                        'wallet' => 'paytm',
                        'method' => 'card',
                        'card' => [
                            'entity' => 'card',
                            'name' => '',
                            'last4' => '5449',
                            'network' => 'MasterCard',
                            'type' => 'credit',
                            'issuer' => 'KKBK',
                            'international' => false,
                            'emi' => false,
                            'sub_type' => null,
                            'token_iin' => null,
                            'expiry_month' => '01',
                            'expiry_year' => '2099',
                            'flows' => [],
                            'cobranding_partner' => null,
                        ],
                        'recurring' => false,
                        'recurring_details' => [
                            'status' => null,
                            'failure_reason' => null,
                        ],
                        'auth_type' => null,
                        'mrn' => null,
                        'used_at' => 1671548162,
                        'created_at' => 1671548162,
                        'expired_at' => 1896114599,
                        'status' => 'active',
                        'notes' => [],
                        'dcc_enabled' => false,
                        'compliant_with_tokenisation_guidelines' => true,
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchCustomerTokensInternalForLocalCustomer' => [
        'request' => [
            'url' => '/internal/customers/tokens',
            'content' => [
                'customer_id' => 'cust_100001customer',
            ],
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 2,
                'items' => [
                    [
                        'id' => 'token_KuClzOupNoxchg',
                        'entity' => 'token',
                        'token' => 'KuClzOvHeBP36p',
                        'bank' => 'ICIC',
                        'wallet' => 'paytm',
                        'method' => 'card',
                        'card' => [
                            'entity' => 'card',
                            'name' => '',
                            'last4' => '1234',
                            'network' => 'Visa',
                            'type' => 'credit',
                            'issuer' => 'HDFC',
                            'international' => false,
                            'emi' => true,
                            'sub_type' => null,
                            'token_iin' => null,
                            'expiry_month' => '01',
                            'expiry_year' => '2099',
                            'flows' => [],
                            'cobranding_partner' => null,
                        ],
                        'recurring' => false,
                        'recurring_details' => [
                            'status' => null,
                            'failure_reason' => null,
                        ],
                        'auth_type' => null,
                        'mrn' => null,
                        'used_at' => 1671548162,
                        'created_at' => 1671548162,
                        'expired_at' => 1896114599,
                        'status' => 'active',
                        'notes' => [],
                        'dcc_enabled' => false,
                        'compliant_with_tokenisation_guidelines' => true,
                    ],
                    [
                        'id' => 'token_KuClzN7vGGpga0',
                        'entity' => 'token',
                        'token' => 'KuClzN8Q5ttGR8',
                        'bank' => 'ICIC',
                        'wallet' => 'paytm',
                        'method' => 'card',
                        'card' => [
                            'entity' => 'card',
                            'name' => '',
                            'last4' => '5449',
                            'network' => 'MasterCard',
                            'type' => 'credit',
                            'issuer' => 'KKBK',
                            'international' => false,
                            'emi' => false,
                            'sub_type' => null,
                            'token_iin' => null,
                            'expiry_month' => '01',
                            'expiry_year' => '2099',
                            'flows' => [],
                            'cobranding_partner' => null,
                        ],
                        'recurring' => false,
                        'recurring_details' => [
                            'status' => null,
                            'failure_reason' => null,
                        ],
                        'auth_type' => null,
                        'mrn' => null,
                        'used_at' => 1671548162,
                        'created_at' => 1671548162,
                        'expired_at' => 1896114599,
                        'status' => 'active',
                        'notes' => [],
                        'dcc_enabled' => false,
                        'compliant_with_tokenisation_guidelines' => true,
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetGlobalCustomerDetailsForCheckoutService' => [
        'request' => [
            'url' => '/internal/customers/checkout',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'email' => 'test@razorpay.com',
                'contact' => '+919988776655',
                'is_global_customer' => true,
                'has_saved_card_tokens' => false,
                'has_saved_addresses' => false,
            ],
        ],
    ],

    'testGetLocalCustomerDetailsForCheckoutService' => [
        'request' => [
            'url' => '/internal/customers/checkout',
            'method' => 'GET',
            'content' => [
                'customer_id' => 'cust_zMRVsGfjoQyc9w', // Filled by the Test
            ],
        ],
        'response' => [
            'content' => [
                'email' => 'testlocalcustomer@razorpay.com',
                'contact' => '+919876543210',
                'is_global_customer' => false,
                'has_saved_card_tokens' => true,
                'has_saved_addresses' => false,
            ],
        ],
    ],
];
