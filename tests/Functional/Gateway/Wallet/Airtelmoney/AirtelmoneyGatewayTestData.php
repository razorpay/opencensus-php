<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment'               => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
        'status'            => 'captured',
        'two_fa_status'     => 'passed',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => 'airtelmoney',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_airtelmoney',
        'terminal_id'       => '100ArtlMnyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentFailureFlow' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
    ],

    'testPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => 50000,
        'wallet'                => 'airtelmoney',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'SUC',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

    'testFailedPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => 1999,
        'wallet'                => 'airtelmoney',
        'received'              => false,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_description'  => 'Invalid MID',
        'status_code'           => 'FAL',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

    'testRefundFailedPayment' => [
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
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_PAYMENT_CREDIT_LESS_THAN_DEBIT,
        ],
    ],

    'testVerifyFailedPayment'   => [
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

    'testRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'airtelmoney',
        'email'                 => 'a@b.com',
        'amount'                => 50000,
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_description'  => 'SUCCESS',
        'status_code'           => 'SUC',
        'entity'                => 'wallet',
    ],

    'testPartialRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'airtelmoney',
        'email'                 => 'a@b.com',
        'amount'                => 25000,
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_description'  => 'SUCCESS',
        'status_code'           => 'SUC',
        'entity'                => 'wallet',
    ],

    'testRefundFailedPaymentEntity' => [
        'action'                => 'refund',
        'wallet'                => 'airtelmoney',
        'email'                 => 'a@b.com',
        'amount'                => 2999,
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_description'  => 'Reversal amount is greater than the amount that can be reversed',
        'status_code'           => 'FAL',
        'entity'                => 'wallet',
    ],
];
