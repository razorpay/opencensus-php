<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testRefundTransferPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_REFUND_NOT_SUPPORTED
        ],
    ],

    'testTransferToInvalidOrUnlinkedId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000000',
                        'amount' => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID
        ],
    ],

    'testReverseAllPartialRefundMultipleTransfers' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The reverse_all parameter is not supported for this refund',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPartialRefundMultipleTransfersReversalsNotDefined' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The reversals parameter is required for this refund request',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'createOrderTransfers' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'    => '50000',
                'currency'  => 'INR',
                'transfers' => [
                    [
                        'account'  => 'acc_10000000000001',
                        'amount'   => '20000',
                        'currency' => 'INR',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testReverseAllOrderTransfers' => [
        'request' => [
            'method' => 'POST',
            'content' => [
                'amount' => '50000',
                'reverse_all' => true,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 50000,
                'currency' => 'INR',
            ],
        ],
    ],
];
