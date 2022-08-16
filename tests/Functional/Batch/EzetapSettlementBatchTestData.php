<?php

namespace RZP\Tests\Functional\Batch;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
     'testValidateEzetapSettlement' => [
         'request'  => [
             'url'     => '/batches/validate',
             'method'  => 'post',
             'content' => [
                 'type'     => 'ezetap_settlement',
             ],
         ],
         'response' => [
             'content' => [
                 'processable_count' => 1,
                 'error_count'       => 0,
             ],
         ],
     ],

    'testValidateEzetapSettlementMissingData' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'     => 'ezetap_settlement',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
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
];

