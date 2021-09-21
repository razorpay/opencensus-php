<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testBatchUploadHitachiChargeback'       => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'created'
            ],
        ],
    ],
    'testBatchUploadHitachiFailedChargeback' => [
        'request'   => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'hitachi_cbk_mastercard',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid headers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],
];
