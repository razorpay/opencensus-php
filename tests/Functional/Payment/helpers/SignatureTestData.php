<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use RZP\Gateway\Hdfc;

return [
    'testValidSignature' =>[
        'request' => [
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'amount' => 50000,
                'currency' => 'INR',
                'merchant_order_id' => 'Grü-1234'
            ]
        ],
    ],

    'testPaymentStatusAfterSignedRequestWith3dSecure' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
                'amount' => 50000,
                'currency' => 'INR',
                'notes' => [
                    'merchant_order_id' => 'Grü-1234'
                ]
            ],
        ],
    ],

    'testPaymentStatusAfterSignedRequestWithout3dSecure' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'status' => 'captured',
                'entity' => 'payment',
                'amount' => 50000,
                'currency' => 'INR',
                'notes' => [
                    'merchant_order_id' => 'Grü-1234'
                ]
            ],
        ],
    ],

    'testCaptureFailAfterSignedRequest' => [
        'request' => [
            'method' => 'POST',
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
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED
        ],
    ],
];
