<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testMerchantConfigsForInvalidReportType' => [
        'request' => [
            'url'     => '/reporting/configs',
            'method'  => 'GET',
            'content' => [

            ],
            'server' => [
                'HTTP_X-Report-Type' => 'random',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid report type',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGettingPartnerConfigsByNonPartner' => [
        'request' => [
            'url'     => '/reporting/configs',
            'method'  => 'GET',
            'content' => [

            ],
            'server' => [
                'HTTP_X-Report-Type' => 'partner',
            ],
        ],
        'response' => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
];
