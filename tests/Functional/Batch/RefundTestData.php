<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testUploadRefundFile' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'total_count' => 1,
                'amount'      => 4000,
                'status'      => 'created',
            ],
        ],
    ],

    'testUploadRefundFileException' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount is not set in the uploaded file',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT,
        ],
    ],

    'testGetRefundFiles' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'get',
            'content' => [
            ],
            'server'  => [
                'HTTP_X-Dashboard'           => 'true',
                'HTTP_X-Dashboard-User-Role' => 'owner',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'        => 'batch',
                        'status'        => 'created',
                        'amount'        => 4000,
                        'total_count'   => 1,
                        'success_count' => 0,
                        'failure_count' => 0,
                    ],
                ],
            ],
        ],
    ],

    'testGetRefundFileWithId' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'get',
            'content' => [
            ],
            'server'  => [
                'HTTP_X-Dashboard'           => 'true',
                'HTTP_X-Dashboard-User-Role' => 'owner',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'status'        => 'created',
                'amount'        => 4000,
                'total_count'   => 1,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],

    'testProcessRefundFile' => [
        'request'  => [
            'url'     => '/batches/process',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'           => 'batch',
                        'status'           => 'processed',
                        'amount'           => 4200,
                        'processed_amount' => 4200,
                        'total_count'      => 2,
                        'success_count'    => 2,
                        'failure_count'    => 0,
                        'attempts'         => 1,
                    ],
                ],
            ],
        ],
    ],

    'testProcessRefundFileWithInvalidFile' => [
        'request'  => [
            'url'     => '/batches/process',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'           => 'batch',
                        'status'           => 'processing',
                        'amount'           => 4000,
                        'processed_amount' => 0,
                        'success_count'    => null,
                        'failure_count'    => null,
                        'attempts'         => 1,
                    ],
                ],
            ],
        ],
    ],

    'testProcessRefundFileWithRefundedBatch' => [
        'request'  => [
            'url'     => '/batches/process',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'           => 'batch',
                        'status'           => 'processed',
                        'amount'           => 4000,
                        'processed_amount' => 4000,
                        'success_count'    => 1,
                        'failure_count'    => 0,
                        'attempts'         => 1,
                    ],
                ],
            ],
        ],
    ],

    'testProcessRefundFileWithInvalidPaymentId' => [
        'request'  => [
            'url'     => '/batches/process',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'           => 'batch',
                        'status'           => 'processing',
                        'amount'           => 8000,
                        'processed_amount' => 4000,
                        'success_count'    => 1,
                        'failure_count'    => 1,
                        'attempts'         => 1,
                        'total_count'      => 2,
                    ],
                ],
            ],
        ],
    ],

    'testProcessRefundWithOneAttempt' => [
        'request'  => [
            'url'     => '/batches/process',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'           => 'batch',
                        'status'           => 'processing',
                        'amount'           => 4000,
                        'processed_amount' => 0,
                        'success_count'    => 0,
                        'failure_count'    => 1,
                        'attempts'         => 1,
                    ],
                ],
            ],
        ],
    ],

    'testProcessRefundWithTwoAttempt' => [
        'request'  => [
            'url'     => '/batches/process',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity'           => 'batch',
                        'status'           => 'processing',
                        'amount'           => 4000,
                        'processed_amount' => 0,
                        'success_count'    => 0,
                        'failure_count'    => 1,
                        'attempts'         => 2,
                    ],
                ],
            ],
        ],
    ],

    'testProcessRefundWithThreeAttempt' => [
        'request'  => [
            'url'     => '',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity'               => 'batch',
                'status'               => 'partially_processed',
                'amount'               => 4000,
                'processed_amount'     => 0,
                'success_count'        => 0,
                'failure_count'        => 1,
                //
                // Assertion: Following attribute (processed_percentage) only comes in admin auth at the moment.
                // Additionally, it's value in this case should be 100 %, but that is asserted in code because
                // it's queue stuff.
                //
                'processed_percentage' => 0,
                'attempts'             => 2,
            ],
        ],
    ],

    'testProcessRefundWithThreeAttemptSuccess' => [
        'request'  => [
            'url'     => '',
            'method'  => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'status'           => 'partially_processed',
                'amount'           => 4000,
                'processed_amount' => 0,
                'success_count'    => 0,
                'failure_count'    => 1,
                'attempts'         => 2,
            ],
        ],
    ],
];
