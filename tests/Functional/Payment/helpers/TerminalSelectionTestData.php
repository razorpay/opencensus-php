<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAnotherMerchantDirectTerminalAssignedToTestAccountAndTestUnassign' => [
        'jsonp' => true,
        'request' => [
            'content' => [
                'callback'  => 'abcdefghijkl',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 200,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\RuntimeException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_RUNTIME_ERROR,
        ],
    ],
];
