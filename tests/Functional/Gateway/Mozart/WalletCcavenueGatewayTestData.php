<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
        'status'            => 'authorized',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'card_id'           => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'ccavenue',
        'signed'            => false,
        'verified'          => null,
    ],
    'testPaymentMozartEntity' => [
        'action'            => 'authorize',
        'gateway'           => 'ccavenue',
        'amount'            => 50000,
    ],
    'testPaymentIdMismatch' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::SERVER_ERROR,
                    'description'       => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\LogicException',
            'internal_error_code'       => ErrorCode::SERVER_ERROR_LOGICAL_ERROR,
        ],
    ],

    'testTamperedAmount' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::SERVER_ERROR,
                    'description'       => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\LogicException',
            'internal_error_code'       => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testVerifyFailed' => [
        'response'  => [
            'content'   => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED
        ]
    ],

    'testAuthFailed' => [
        'response'  => [
            'content'     => [
                'error'         => [
                    'code'              => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'       => "Your payment didn't go through due to a temporary issue. Any debited amount will be refunded in 4-5 business days.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                     => 'RZP\Exception\GatewayErrorException',
            'internal_error_code'       => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],
    'testAuthFailedVerifySuccess' => [
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
            'class'                 => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],
    'testAuthSuccessVerifyFailed' => [
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
            'class'                 => RZP\Exception\PaymentVerificationException::class,
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],
];
