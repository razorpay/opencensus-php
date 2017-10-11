<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPaymentNetbankingEntity' => [
        'action'          => 'authorize',
        'amount'          => 20,
        'bank'            => 'UTIB',
        'received'        => true,
        'error_message'   => null,
        'schedule_status' => 'Y',
        'status'          => 'Y',
        'entity'          => 'netbanking',
    ],

    'testPaymentVerifyFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED
        ],
    ]
];
