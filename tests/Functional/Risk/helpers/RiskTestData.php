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

    'testAppendComments' => [
        'request' => [
            'url' => '/risk/%s',
            'method' => 'PUT',
            'content' => [
                'fraud_type' => 'confirmed',
                'source' => 'internal',
                'comments' => 'Confirmed with merchant',
            ],
        ],
        'response' => [
            'content' => [
                'fraud_type' => 'confirmed',
                'comments' => 'PAYMENT_SUSPECTED_FRAUD_BY_MAXMIND || Confirmed with merchant',
            ],
            'status_code' => 200,
        ],
    ],
];
