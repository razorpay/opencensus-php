<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testMandateGenerationFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
        ]
    ],
    'testMandateSigningFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_MANDATE_CREATION_FAILED,
        ]
    ],
    'testMandateSigningTimeout' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 504,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayTimeoutException::class,
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_REQUEST_TIMEOUT,
        ]
    ],
];
