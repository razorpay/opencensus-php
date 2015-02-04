<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

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
                'merchant_order_id' => 'random order id'
            ]
        ],
    ],

    'testInvalidEmailInPayment' => [
        'request' => [
            'content' => [
                'email' => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'email',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
];