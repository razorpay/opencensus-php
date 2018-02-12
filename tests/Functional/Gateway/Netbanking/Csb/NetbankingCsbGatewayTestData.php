<?php

namespace RZP\Tests\Functional\Gateway\Netbanking\Csb;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;

return [
    'testPayment' => [
        'amount'          => 500,
        'action'          => 'authorize',
        'bank'            => 'CSBK',
        'bank_payment_id' => '9999999999',
        'status'          => 'Y',
        'reference1'      => 'RazorPay',
        'received'        => true,
    ],

    'testPaymentFailed' => [
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
            'class'                 => GatewayErrorException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testPaymentFailedNetbankingEntity' => [
        'amount'          => 500,
        'action'          => 'authorize',
        'bank'            => 'CSBK',
        'bank_payment_id' => '9999999999',
        'status'          => 'N',
        'reference1'      => 'RazorPay',
        'received'        => true,
    ],

    'testVerifyMismatch' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],
];
