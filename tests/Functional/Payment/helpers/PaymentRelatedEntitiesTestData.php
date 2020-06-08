<?php

use RZP\Error\ErrorCode;
use RZP\Exception\BadRequestValidationFailureException;

return [
    'testFetchOrdersEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity'   => 'order',
            ],
        ],
    ],

    'testFetchOrdersEntityInvalidId' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
        'response' => [
            'content' => [
                'error'   => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testFetchMerchantsEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity'   => 'merchant',
            ],
        ],
    ],

    'testFetchFeaturesEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity'   => 'feature',
            ],
        ],
    ],

    'testFetchTokenEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity'   => 'token',
            ],
        ],
    ],

    'testFetchIinsEntity' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'iin'       => '411111',
                "category"  => "CLASSIC",
                "network"   => "Visa",

            ],
        ],
    ],

];
