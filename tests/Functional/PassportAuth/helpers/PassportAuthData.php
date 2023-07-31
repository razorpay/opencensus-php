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
];
