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

    'testMerchantAuthWithImpersonationCanSkipWorkflow' => [
        'request'   => [
            'url'     => '/merchants/onboarding/escalations',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Passport-JWT-V1' => '',
                'HTTP_X-PASSPORT-USABLE' => 'true'
            ]
        ],
        'response' => [
            'content' => [
                'limit' => [
                    'settlement' => 1500000,
                    'payment' => 1000000000,
                ]
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
];
