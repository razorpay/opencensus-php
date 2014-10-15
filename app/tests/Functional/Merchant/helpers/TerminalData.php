<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testAssignTerminal' => [
        'request' => [
            'content' => [
                'gateway' => 'hdfc',
                'gateway_merchant_id' => '123abcd',
                'gateway_terminal_id' => '123abcde',
                'gateway_terminal_password' => '123abcdef'
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gateway_merchant_id' => '123abcd',
                'gateway_terminal_id' => '123abcde',
            ]
        ]
    ],

    'testReassignTerminal' => [
        'request' => [
            'content' => [
                'gateway' => 'hdfc',
                'gateway_merchant_id' => '123abcd',
                'gateway_terminal_id' => '123abcde',
                'gateway_terminal_password' => '123abcdef'
            ],
            'url' => '/merchants/363e4efa820b0c06208ccd99/terminal',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_TERMINAL_EXISTS_FOR_GATEWAY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'EE\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_TERMINAL_EXISTS_FOR_GATEWAY,
        ],
    ]
];