<?php

use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'validPassportFlowData' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ],
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'items' => [
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'badRequestFlowData' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ],
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => '',
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testPassportAuthOnInternalRoute' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments/timeout',
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPassportAuthOnAdminRoute' => [
        'request' => [
            'url' => '/orgs',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => []
            ],
            'status_code' => 200,
        ],
    ],

    'testPassportAuthOnProxyRoute' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/webhooks/events/all',
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testPassportAuthOnDirectRoute' => [
        'request' => [
            'method' => 'GET',
            'url'    => '/checkout/public',
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'status_code' => 200,
        ],
    ],

    'testPassportAuthOnDeviceRoute' => [
        'request' => [
            'url' => '/upi/devices/dev_RazorpayDevice',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testMerchantAuthWithImpersonation' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'notes'         => ['key' => 'value']
            ],
            'method'    => 'POST',
            'url'       => '/orders',
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true',
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
            ]
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
            'status_code' => 200,
        ],
    ],

    'testOauthWithoutRazorpayXFeatureEnabled' => [
        'request'  => [
            'url'    => '/users/otp/send',
            'method' => 'POST',
            'content' => [
                'medium' => 'sms',
                'action' => 'verify_contact',
            ],
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_ACCESS_TO_RAZORPAYX_RESOURCE
                ]
            ],
            'status_code' => 401
        ],
    ],
    'testPublicAuth' => [
        'request' => [
            'method' => 'POST',
            'url' => '/payments',
            'content' => [
                'amount'            => '50000',
                'currency'          => 'INR',
                'email'             => 'a@b.com',
                'contact'           => '9918899029',
                'notes'             => [
                    'merchant_order_id' => 'random order id',
                ],
                'description'       => 'random description',
                'bank'              => 'UCBA',
                'card'=>[
                    'number'            => '4012001038443335',
                    'name'              => 'Harshil',
                    'expiry_month'      => '12',
                    'expiry_year'       => '2024',
                    'cvv'               => '566'
                ]
            ],
            'server' => [
                'HTTP_X-PASSPORT-USABLE' => 'false'
            ]
        ],
        'response' => [
            'status_code' => 200
        ],
    ],
    'testPartnerPublicAuth' => [
        'request'   => [
            'url'     => '/emi',
            'method'  => 'get',
            'content' => [],
            'server'  => [
                'HTTP_X-Razorpay-Account' => 'acc_100000Razorpay',
                'HTTP_X-PASSPORT-USABLE' => 'false'
            ],
        ],
        'response' => [
            'content' => [
                'HDFC' => [
                    'min_amount' => 500000,
                    'plans' => [
                        '9' => 12,
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],
    'inValidAppAuthWithEdgePassport' => [
        'request' => [
            'method' => 'GET',
            'url' => '/payments',
            'content' => [
                'count' => 1
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_API_KEY
                ],
            ],
            'status_code' => 401,
        ],
    ],

    'paymentsCreateAjax' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'paymentsCreateCheckout' => [
        'request' => [
            'url' => '/payments/create/checkout',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],
];
