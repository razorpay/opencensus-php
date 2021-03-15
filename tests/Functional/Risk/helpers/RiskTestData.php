<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testFraudDetected' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_POSSIBLE_FRAUD,
            'public_error_description' => ''
        ],
    ],
    'testFetchMultiple' => [
        'request' => [
            'url' =>'/risk?',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 1,
            ]
        ]
    ],
];
