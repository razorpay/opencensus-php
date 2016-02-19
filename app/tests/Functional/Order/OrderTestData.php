<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
        ],
    ],

    'testGetOrder' => [
        'amount'        => 50000,
        'currency'      => 'INR',
        'receipt'       => 'rcptid42',
    ],

    'testGetMultipleOrders' => [
        'request' => [
            'url' => '/orders',
            'method' => 'get',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testStatusAfterPayment' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testOrderAndPaymentAmountMismatch' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH
        ],
    ]
];
