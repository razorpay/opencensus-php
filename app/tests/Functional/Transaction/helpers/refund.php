<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testRefund' => [
        'request' => [
        ],
        'response' => [
            'content' => [
                'entity' => 'refund',
                'amount' => 50000,
                'currency' => 'INR',
            ],
        ],
    ],

    'testMultipleRefunds' => [
        'request' => [
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'entity' => 'transaction',
                'amount' => 50000,
                'amount_refunded' => 50000,
                'refund_status' => 'full',
                'status' => 'captured',
                'currency' => 'INR',
            ],
        ],
    ],

    'testRefundWithHigherAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_CAPTURED
        ],
    ],

  'testMultipleRefundsWithHigherAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSACTION_REFUND_AMOUNT_GREATER_THAN_UNREFUNDED
        ],
    ],

    'testRefundOnRefundedTransaction' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSACTION_FULLY_REFUNDED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSACTION_FULLY_REFUNDED
        ],
    ],

    'testRefundOnAuthorizedTransaction' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSACTION_STATUS_NOT_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSACTION_STATUS_NOT_CAPTURED
        ],
    ],

    'testRefundWithNegativeAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testRefundWithZeroAmount' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
];
