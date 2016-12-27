<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Terminal\Shared;

return [
    'testTransferToInvalidOrUnlinkedId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000000',
                        'amount' => 100
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
    'testTransferWithFeatureNotEnabled' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount' => 100
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'marketplace is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testMultipleTransfersOnSameAccountId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount' => 100
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_MULTIPLE_TRANSFERS_TO_SAME_ACCOUNT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_MULTIPLE_TRANSFERS_TO_SAME_ACCOUNT
        ],
    ],
    'testTransferPaymentAmountGreaterThanCaptured' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_AMOUNT_GREATER_THAN_CAPTURED
        ],
    ],
    'dummy' => [
        'content' => [
            'count' => 1,
            'items' => [
                [
                    'source_type'     => 'payment',
                    'source_id'       => '000',
                    'to_type'         => 'merchant',
                    'to_id'           => 'acc_10000000000001',
                    'amount'          => 4000,
                    'amount_reversed' => 0
                ],
            ],
        ],
    ],
];
