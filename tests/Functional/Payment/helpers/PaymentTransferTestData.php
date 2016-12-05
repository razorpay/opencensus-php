<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Terminal\Shared;

return [
    'testCaptureAndTransferToInvalidCustomerId' => [
        'request' => [
            'content' => [
                'amount' => 200,
                'transfers' => [
                    [
                        'customer' => 'cust_asd',
                        'amount'   => 200
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The customer must be 19 characters.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testCaptureAndTransferToUnknownCustomerId' => [
        'request' => [
            'content' => [
                'amount' => 200,
                'transfers' => [
                    [
                        'customer' => 'cust_3030300000cust',
                        'amount'   => 200
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],
    'testTransferToExistingCustomerWithNoExistingWallet' => [
        'request' => [
            'content' => [
                'amount' => 200,
                'transfers' => [
                    [
                        'customer' => null,
                        'amount'   => null
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'status' => 'captured',
                'entity' => 'payment',
            ],
            'status_code' => 200,
        ],
    ],

];
