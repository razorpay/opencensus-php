<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'netbanking',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'card_id'           => null,
        'bank'              => 'RATN',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
        'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'netbanking_rbl',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'terminal_id'       => '100NbRblTermnl',
    ],

    'testTpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'RATN',
                'account_number' => '04030403040304',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank'            => 'RATN',
        'status'          => 'SUC',
    ],

    'testPaymentVerifySuccessEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank'            => 'RATN',
        'status'          => 'SUC'
    ],

    'testAuthFailedVerifySuccessEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank'            => 'RATN',
        'status'          => 'SUC'
    ],

    'testPaymentFailedNetbankingEntity' => [
        'bank_payment_id' => '99999999',
        'received'        =>  true,
        'bank'            => 'RATN',
        'status'          => 'FAL'
    ],

    'testAuthSuccessVerifyFailedNetbankingEntity' => [
        'received'        => true,
        'bank'            => 'RATN',
        'status'          => 'SUC'
    ],

    'testAuthFailedVerifyFailedEntity' => [
        'received'        => true,
        'bank'            => 'RATN',
        'status'          => 'FAL'
    ],

    'testAuthorizeFailed' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
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
            'class'                 => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],
];
