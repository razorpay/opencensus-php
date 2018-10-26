<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testDigioCancelledByUser' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_AT_EMANDATE_REGISTRATION,
        ],
    ],
];
