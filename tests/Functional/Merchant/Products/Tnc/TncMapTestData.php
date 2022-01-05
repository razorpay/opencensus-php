<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateTnC' => [
        'request' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'business_unit' => 'payments',
            ],
            'url'    => '/products/tnc/',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active',
                'business_unit' => 'payments'
            ]
        ]
    ],
    'testUpdateTnC' => [
        'request' => [
            'content' => [
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active',
                'business_unit' => 'payments'
            ],
            'url'    => '/products/tnc/{id}',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active',
                'business_unit' => 'payments'
            ]
        ]
    ],
    'testGetTncById' => [
        'request' => [
            'url'    => '/products/tnc/{id}',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'product_name' => 'all',
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active',
                'business_unit' => 'payments'
            ]
        ]
    ],
    "testUpdateTnCWrongBU" => [
        'request' => [
            'content' => [
                'content'      =>  [
                    'terms' => 'https://razorpay.com/terms/'
                ],
                'status'       => 'active',
                'business_unit' => 'payment'
            ],
            'url'    => '/products/tnc/{id}',
            'method' => 'PATCH'
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The business unit requested is invalid',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_BUSINESS_UNIT,
        ],
    ]
];
