<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testUploadRefundFile' => [
        'request' => [
            'url' => '/batches',
            'method' => 'post',
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
        'request' => [
            'url' => '/batches',
            'method' => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount is not set in the uploaded file',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_AMOUNT,
        ],
    ],

    'testGetRefundFiles' => [
        'request' => [
            'url' => '/batches',
            'method' => 'get',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity' => 'batch',
                        'status' =>  'created',
                    ],
                ]
            ],
        ],
    ],

    'testProcessRefundRetryAfterProccessed' => [
        'request' => [
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],

    'testProcessRefundFile' => [
        'request' => [
            'url' => '/batches/process',
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'            => 'batch',
                        'status'            => 'processed',
                        'amount'            =>  4000,
                        'processed_amount'  =>  4000,
                        'success_count'     =>  1,
                        'failure_count'     =>  0,
                        'attempts'          =>  1,
                    ],
                ]
            ],
        ],
    ],

    'testProcessRefundWithOneAttempt' => [
        'request' => [
            'url' => '/batches/process',
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'            => 'batch',
                        'status'            => 'processing',
                        'amount'            =>  4000,
                        'processed_amount'  =>  0,
                        'success_count'     =>  0,
                        'failure_count'     =>  1,
                        'attempts'          =>  1,
                    ],
                ]
            ],
        ],
    ],

    'testProcessRefundWithTwoAttempt' => [
        'request' => [
            'url' => '/batches/process',
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'            => 'batch',
                        'status'            => 'processing',
                        'amount'            =>  4000,
                        'processed_amount'  =>  0,
                        'success_count'     =>  0,
                        'failure_count'     =>  1,
                        'attempts'          =>  2,
                    ],
                ]
            ],
        ],
    ],

    'testProcessRefundWithThreeAttempt' => [
        'request' => [
            'url' => '/batches/process',
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'            => 'batch',
                        'status'            => 'processed',
                        'amount'            =>  4000,
                        'processed_amount'  =>  0,
                        'success_count'     =>  0,
                        'failure_count'     =>  1,
                        'attempts'          =>  3,
                    ],
                ]
            ],
        ],
    ],

    'testProcessRefundWithThreeAttemptSuccess' => [
        'request' => [
            'url' => '/batches/process',
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'entity'            => 'batch',
                        'status'            => 'processed',
                        'amount'            =>  4000,
                        'processed_amount'  =>  4000,
                        'success_count'     =>  1,
                        'failure_count'     =>  0,
                        'attempts'          =>  3,
                    ],
                ]
            ],
        ],
    ],

    'testProcessRetryRefundWithThreeAttempt' => [
        'request' => [
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                        'entity'            => 'batch',
                        'status'            => 'processing',
                        'amount'            =>  4000,
                        'processed_amount'  =>  0,
                        'success_count'     =>  0,
                        'failure_count'     =>  1,
                        'attempts'          =>  2,
            ],
        ],
    ],

];
