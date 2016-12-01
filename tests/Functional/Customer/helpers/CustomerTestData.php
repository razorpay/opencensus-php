<?php

use RZP\Gateway\Hdfc;
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
                'name'    => 'testc',
                'email'   => 'test@razorpay.com',
                'contact' => '1234567899',
            ],
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
            ],
        ],
        'response' => [
            'content' => [
                'name'    => 'test1',
                'contact' => '1234567809',
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

    'testDeleteCustomer' => [
        'request' => [
            'url' => '/customers/cust_100000customer',
            'method' => 'delete',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
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
                        'token'         => '100wallettoken',
                        'method'        => 'wallet',
                        'wallet'        => 'paytm',
                    ],
                    [
                        'token'         => '10000cardtoken',
                        'method'        => 'card',
                        'card'          =>  [
                            'last4'         => '1111',
                            'network'       => 'Visa',
                        ]
                    ],
                    [
                        'token'         => '10000banktoken',
                        'method'        => 'netbanking',
                        'bank'          => 'HDFC',
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
                'card_id' => '10000savedcard',
            ],
        ],
        'response' => [
            'content' => [
                'method' => 'card',
                'card'   =>  [
                    'last4'   => '1111',
                    'network' => 'Visa',
                ],
                'wallet'    => null,
                'bank'      => null
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
                'entity'    => 'collection',
                'items'     =>  [
                    [
                        'token' => '1000gcardtoken',
                        'card'  => [
                            'last4'     => '1111',
                            'network'   => 'Visa'
                        ],
                    ]
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
];
