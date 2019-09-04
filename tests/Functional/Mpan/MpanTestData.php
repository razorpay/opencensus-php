<?php


use RZP\Error\ErrorCode;

return [
    'testMpanIssue' =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'Visa',
                'count'   => 10,
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 10,
                'items'     => [
                ],
            ],
        ],
    ],

    'testMpanIssueCountExceedsAllowedLimit'  =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'MasterCard',
                'count'   => 50000,
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMpanIssueInvalidNetwork'  =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'Amex',
                'count'   => 4,
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMpanIssueRequestedCountUnavailable'  =>  [
        'request' => [
            'url' => '/mpans/issue',
            'method' => 'post',
            'content' => [
                'network' => 'Visa',
                'count'   => 15, //we have added only 10 visa mpans into table during test setup
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\ServerErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_REQUESTED_MPANS_NOT_AVAILABLE,
        ],
    ],
];
