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
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TRANSFER_INVALID_ACCOUNT_ID
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TRANSFER_INVALID_ACCOUNT_ID
        ],
    ],
    'testTransferWithFeatureNotEnabled' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ],
            ],
            'status_code' => 400,
        ],
    ],
    'testMultipleTransfersOnSameAccountId' => [
        'request' => [
            'content' => [
                'transfers' => [
                    [
                        'account' => 'acc_10000000000001',
                        'amount'  => 100,
                        'currency'=> 'INR',
                    ],
                ]
            ]
        ],
        'response'  => [
            'content'     => [
                'count' => 1,
                'items' => [
                    [
                        'entity'    => 'transfer',
                        'recipient' => 'acc_10000000000001',
                        'amount'    => 100,
                        'currency'  => 'INR',
                    ],
                ],
            ],
            'status_code' => 200,
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
    'testTransferToCustomerAndAccount' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_TRANSFER_MULTIPLE_ENTITY_TYPES_GIVEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_TRANSFER_MULTIPLE_ENTITY_TYPES_GIVEN
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
