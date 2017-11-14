<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateBatchOfPaymentLinkType1' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'payment_link',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => null,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateBatchOfPaymentLinkType2' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile1' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid headers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile2' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file does not have any entries',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_EMPTY,
        ],
    ],

    'testCreateBatchOfPaymentLinkTypeWithInvalidFile3' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_link',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded batch payment link file does not contain proper values',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_PAYMENT_LINK_FILE_ERRORS,
        ],
    ],
];
