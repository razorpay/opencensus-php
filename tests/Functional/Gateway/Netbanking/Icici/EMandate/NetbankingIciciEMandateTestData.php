<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPaymentNetbankingEntity' => [
        'action'          => 'authorize',
        'amount'          => 20,
        'bank'            => 'ICIC',
        'received'        => true,
        'error_message'   => null,
        'schedule_status' => 'Y',
        'status'          => 'Y',
        'entity'          => 'netbanking',
    ],

    'testEMandateInitialPaymentFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testSiRecurringStatusNotSet' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => RZP\Exception\LogicException::class,
            'internal_error_code'   => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],
];
