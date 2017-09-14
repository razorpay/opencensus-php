<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Netbanking\Bob\Constants;

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
        'bank'              => 'BARB',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
        'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'netbanking_bob',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'terminal_id'       => '100NbBbdaTrmnl',
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => 'AB1234',
        'received'        => true,
        'bank'            => 'BARB',
        'status'          => 'S',
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

    'testPaymentFailedNetbankingEntity' => [
        'bank_payment_id' => null,
        'received'        => false,
        'bank'            => 'BARB',
        'status'          => Constants::STATUS_FAILURE
    ],

    'testPaymentVerifySuccessEntity' => [
        'bank_payment_id' => 'AB1234',
        'received'        => true,
        'bank'            => 'BARB',
        'status'          => Constants::STATUS_SUCCESS
    ],

    'testAuthFailedVerifySuccessEntity' => [
        'bank_payment_id' => 'AB1234',
        'received'        => false,
        'bank'            => 'BARB',
        'status'          => Constants::STATUS_SUCCESS
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
