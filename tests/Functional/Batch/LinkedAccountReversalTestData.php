<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateBatchForLAReversal' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account_reversal',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'amount'      => 3000,
                'status'      => 'created',
                'total_count' => 3,
            ],
        ],
    ],

    'testCreateBatchForLAReversalWithoutPermission' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account_reversal',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Refunds are not allowed on this linked account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateBatchForLAReversaDulplicateEntry' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account_reversal',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file should not have multiple entries for the same Transfer Id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_DUPLICATE_TRANSFER_ID,
        ],
    ],

    'testUploadLAReversalFileException' => [
        'request'   => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account_reversal',
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

    'testGetLAReversalFiles' => [
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
                        'amount'        => 3000,
                        'total_count'   => 3,
                        'success_count' => 0,
                        'failure_count' => 0,
                    ],
                ],
            ],
        ],
    ],

    'testGetLAReversalFileWithId' => [
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
                'amount'        => 3000,
                'total_count'   => 3,
                'success_count' => 0,
                'failure_count' => 0,
                'attempts'      => 0,
            ],
        ],
    ],

    'testProcessLAReversalFileWithValidAndInvalidTransfers' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'linked_account_reversal',
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'batch',
                'amount'        => 1200,
                'status'        => 'created',
                'total_count'   => 2,
            ],
        ],
    ],
];
