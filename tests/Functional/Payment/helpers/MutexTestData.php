<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testMutexAcquiredCaptureRequest' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS
        ],
    ],

    'testMutexAcquiredRefundRequest' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ANOTHER_OPERATION_IN_PROGRESS
        ],
    ],

    'testCaptureRequest' => [
        'entity'            => 'payment',
        'amount'            => 50000,
        'currency'          => 'INR',
        'status'            => 'captured',
        'order_id'          => null,
        'method'            => 'card',
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'captured'          => true,
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'error_code'        => null,
        'error_description' => null
    ],
];
