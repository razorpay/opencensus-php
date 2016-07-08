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
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => 'olamoney',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_olamoney',
        'terminal_id'       => '1000OlamoneyTl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],

    'testPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => 50000,
        'wallet'                => 'olamoney',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '+919918899029',
        'gateway_merchant_id'   => 'random_id',
        'status_code'           => 'success',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

     'testRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'olamoney',
        'email'                 => 'a@b.com',
        'amount'                => 50000,
        'contact'               => '+919918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_code'         => '',
        'status_code'           => 'success',
        'entity'                => 'wallet',
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
];
