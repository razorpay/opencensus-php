<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAuthPaymentRefund' => [
        'amount'                => 200,
        'merchant_id'           => '10000000000000',
    ],
    'testPartialRefundPayment' => [
        'amount'                => 500,
        'merchant_id'           => '10000000000000',
    ],
    'testPaymentInsufficientBalance' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment could not be completed due to insufficient wallet balance. Try another payment method.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_INSUFFICIENT_BALANCE,
        ],
    ],

];
