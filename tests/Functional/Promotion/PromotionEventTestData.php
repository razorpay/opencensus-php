<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testDuplicateEventCreateTest' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/promotions/events',
            'content' => [
                'name'        => 'sign up',
                'description' => 'sign up increment',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Bad request, another event exists with same name',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ANOTHER_PROMOTION_EVENT_ALREADY_EXISTS
        ],
    ],
];
