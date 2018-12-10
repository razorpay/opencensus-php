<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testPayoutRouteWithAccess' => [
        'error' => [
            'code'        => 'BAD_REQUEST_ERROR',
            'description' => 'The api key provided is invalid'
        ]
    ],

    'testPayoutVpaVerifyForIncorrectPayoutReference' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'The server encountered an error. The incident has been reported to admins.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\LogicException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_LOGICAL_ERROR
        ],
    ],
];

