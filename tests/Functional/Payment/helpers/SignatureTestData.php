<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

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

    'testInvalidMerchantOrderId' =>[
        'request' => [
            'content' => [
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
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
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
            'content' => [
                'amount' => 50000,
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ALREADY_CAPTURED
        ],
    ],
];
