<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\GatewayErrorException;

return [
    'testPaymentAndRefund'      => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'paylater',
        'status'            => 'captured',
        'two_factor_auth'   => null,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'paylater',
        'terminal_id'       => '10PLaterFlxTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'hdfc'
    ],

    'testPaymentEntityAfterRefund'      => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'paylater',
        'status'            => 'refunded',
        'two_factor_auth'   => null,
        'amount_authorized' => 50000,
        'amount_refunded'   => 50000,
        'refund_status'     => 'full',
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'paylater',
        'terminal_id'       => '10PLaterFlxTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'hdfc'
    ],

    'testPaymentAuthorized'      => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'paylater',
        'status'            => 'authorized',
        'two_factor_auth'   => null,
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'paylater',
        'terminal_id'       => '10PLaterFlxTml',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null,
        'wallet'            => 'hdfc'
    ],

    'testPaymentCardlessEmiEntityFlexmoney'   => [
        'provider'              => 'flexmoney',
        'currency'              => 'INR',
        'gateway_reference_id'  => '12345678',
        'status'                => 'authorized',
        'error_code'            => null,
        'error_description'     => null,
        'action'                => 'authorize',
        'amount'                => '50000',
        'gateway'               => 'paylater',
        'contact'               => '+919918899029',
        'refund_id'             => null,
        'entity'                => 'cardless_emi'
    ],

    'testPaymentCaptureEntity' => [
        'action'                => 'capture',
        'amount'                => '50000',
        'currency'              => 'INR',
        'entity'                => 'cardless_emi',
        'provider'              => 'flexmoney',
        'status'                => 'captured',
        'error_code'            => null,
        'error_description'     => null,
        'gateway'               => 'paylater',
        'contact'               => '+919918899029',
        'refund_id'             => null,
    ],

    'testPaymentRefundEntity' => [
        'action'                => 'refund',
        'amount'                => '50000',
        'currency'              => 'INR',
        'entity'                => 'cardless_emi',
        'provider'              => 'flexmoney',
        'status'                => 'Success',
        'error_code'            => null,
        'error_description'     => null,
        'gateway'               => 'paylater',
        'contact'               => '+919918899029',
    ],

    'testCapturePaymentFailed'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::GATEWAY_ERROR,
                    'description' => PublicErrorDescription::GATEWAY_ERROR,
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_CAPTURE_FAILED
        ],
    ],

    'testPaymentVerifyFailed'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED
        ],
    ],
];
