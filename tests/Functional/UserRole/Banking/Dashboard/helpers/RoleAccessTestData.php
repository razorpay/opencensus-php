<?php


use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [

    'testGrantAccessWhenExperimentOff' => [
        'request'  => [
            'url'    => '/contacts/cont_1000000contact',
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'cont_1000000contact',
                'entity' => 'contact',
            ],
        ],
    ],

    'testDenyAccessWhenExperimentOn' => [
        'request'  => [
            'url'    => '/contacts',
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testCheckPermissionsForBankingLegacyRoles' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin' => 'https://x.razorpay.com'
            ],
        ],

        'response'      => [
            'content'     => [
                'merchants' => [
                    [],
                    [
                        'banking_role' => 'owner',
                        'role'         => null,
                    ]
                ],
            ],
        ],
    ],
];
