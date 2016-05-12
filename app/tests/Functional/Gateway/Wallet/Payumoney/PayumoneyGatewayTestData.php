<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

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
        'wallet'            => 'payumoney',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '9918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_payumoney',
        'terminal_id'       => '100PayumnyTmnl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
    ],

    'testOtpRetryPayment'       => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                    'action'      => 'RETRY'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        ],
    ],

    'testOtpRetrySuccessPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
                    'action'      => 'RETRY'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'EE\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_OTP_INCORRECT,
        ],
    ],

    'testPaymentWalletEntity' => [
        'action'                => 'authorize',
        'amount'                => 50000,
        'wallet'                => 'payumoney',
        'received'              => true,
        'email'                 => 'a@b.com',
        'contact'               => '9918899029',
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_description'  => 'Use wallet successful',
        'status_code'           => '0',
        'refund_id'             => null,
        'entity'                => 'wallet',
    ],

    'testRefundPayment'       => [
        'action'                => 'refund',
        'wallet'                => 'payumoney',
        'email'                 => 'a@b.com',
        'amount'                => 50000,
        'contact'               => '9918899029',
        'gateway_merchant_id'   => 'random_id',
        'response_code'         => '',
        'response_description'  => 'Refund Initiated',
        'status_code'           => '0',
        'entity'                => 'wallet',
    ],
];