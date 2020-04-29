<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSubMerchantAssignWithMultipleAssignments' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_SUB_MERCHANT_ALREADY_ASSIGNED_TO_TERMINAL,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SUB_MERCHANT_ALREADY_ASSIGNED_TO_TERMINAL,
        ],
    ],

    'testGatewayFilterRejectsCyberSource' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => \RZP\Exception\RuntimeException::class,
            'message'             => 'Terminal should not be null',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testGatewayFilterRejectsMigsForZomato' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => \RZP\Exception\RuntimeException::class,
            'message'             => 'Terminal should not be null',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],

    'testUpiFilterRejectsMindgate' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => \RZP\Exception\RuntimeException::class,
            'message'             => 'Terminal should not be null',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],
    'testUpiOtmCollectRejectTerminal' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => ErrorCode::SERVER_ERROR,
                    'description' => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => \RZP\Exception\RuntimeException::class,
            'message'             => 'Terminal should not be null',
            'internal_error_code' => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],
];
