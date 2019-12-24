<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestValidationFailureException;
use RZP\Exception\GatewayErrorException;
use RZP\Exception\PaymentVerificationException;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 500,
        'method'            => 'paylater',
        'status'            => 'captured',
        'amount_authorized' => 500,
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
        'gateway'           => 'paylater',
        'terminal_id'       => '1ShrdSimplTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'getsimpl'
    ],


    'testPaymentMozartEntity' => [
        'action'            => 'authorize',
        'gateway'           => 'paylater',
        'amount'            =>  500,
    ],

    'testRefundFailed'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_REFUND_FAILED
        ],
    ],

    'testInvalidOtt'   => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Token provided is invalid for paylater',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPaymentVerifyFailed' => [
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
];
