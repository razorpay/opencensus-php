<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testNotEnrolledFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_CONTACT_ISSUING_BANK,
        ],
    ],

    'testFirstAndSecondRecurringPaymentWithSingleDigitMonth' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFirstAndSecondRecurringPayment' => [
        'request' => [
            'content' => [],
            'method'    => 'POST',
            'url'       => '/reminders/send/test/payment/card_auto_recurring/%s',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testEmptyApprovalCode' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        ],
    ],

    'testAuthorizationFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_DECLINED_3DSECURE_AUTH_FAILED,
        ],
    ],

    'testEnrollFailedWithDifferentXml' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'   => ErrorCode::GATEWAY_ERROR_UNKNOWN_ERROR,
        ],
    ],
];
