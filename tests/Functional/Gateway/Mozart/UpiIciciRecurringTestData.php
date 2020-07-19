<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testRecurringMandateCreateRejected' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ]
    ],
    'testRevokeCreatedMandate' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL
                ]
            ],
            'status_code'           => 400
        ],
        'exception' => [
            'class'                 => RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_INVALID_TOKEN_FOR_CANCEL,
        ]
    ],
];
