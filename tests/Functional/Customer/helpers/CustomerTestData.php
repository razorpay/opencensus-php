<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

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

    'testGetCustomerTokensByAppToken' => [
        'request' => [
            'url' => '/apps/tokens',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items'  => [
                    [
                        'token' => '1000gcardtoken',
                        'card'  => [
                            'last4'   => '1111',
                            'network' => 'Visa',
                        ],
                    ],
                ],
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

    'testPauseNotSupportedCardTokens' => [
        'request' => [
            'url' => '/tokens/pause_not_supported/card',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'failed' => [],
                'succeeded' => [],
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

];
