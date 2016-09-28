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
                'entity' => 'batch',
                'status' =>  'created',
            ],
        ],
    ],

    'testDownloadRefundFile' => [
        'request' => [
            'method' => 'post',
            'content' => [
                'type' => 'refund',
            ],
        ],
        'response' => [

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
                    'description' => 'The uploaded file does not contain proper values',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FILE_VALIDATION,
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

    'testRetryRefundFiles' => [
        'request' => [
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                    'id' => 'rfnd_file_6JaB3AUIkCh9kL',
                    'entity' => 'refund_file',
                    'status' =>  'created',
                    'created_at' => 1474032787,
            ],
        ],
    ],

    'testRetryRefundFilesWithException' => [
        'request' => [
            'method' => 'post',
            'content' => [

            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file is already processed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FILE_ALREADY_PROCESSED,
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
                    'id' => 'rfnd_file_6JaB3AUIkCh9kL',
                    'entity' => 'refund_file',
                    'status' =>  'created',
                    'created_at' => 1474032787,
            ],
        ],
    ],
];