<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Gateway\Netbanking\Bob\Status;

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
        'bank'              => 'BARB_R',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id'   => 'random order id',
        ],
        'acquirer_data'     => [
            'bank_transaction_id' => '12345678',
        ],
        'gateway'           => 'netbanking_bob',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'terminal_id'       => '100NbBbdaTrmnl',
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => '12345678',
        'received'        => true,
        'bank'            => 'BARB_R',
        'status'          => 'S',
    ],

    'testPaymentOnCorporate' => [
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
        'bank'              => 'BARB_C',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id'   => 'random order id',
        ],
        'acquirer_data'     => [
            'bank_transaction_id' => '12345678',
        ],
        'gateway'           => 'netbanking_bob',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'terminal_id'       => '100NbBbdaTrmnl',
    ],

    'testPaymentOnCorporateNetbankingEntity' => [
        'bank_payment_id' => '12345678',
        'received'        => true,
        'bank'            => 'BARB_C',
        'status'          => 'S',
    ],

    'testAuthorizationFailure' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment didn't go through as it was declined by the bank. Try another payment method or contact your bank.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testUserCancelledPayments' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => "Your payment didn't go through as it was declined by the bank. Try another payment method or contact your bank.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED,
        ],
    ],

    'testPaymentAmountMismatch' => [
        'response' => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_AMOUNT_TAMPERED,
        ],
    ],

    'testPaymentFailedNetbankingEntity' => [
        'bank_payment_id' => null,
        'received'        => true,
        'bank'            => 'BARB_R',
        'status'          => Status::FAILURE
    ],

    'testAuthFailedVerifyFailedEntity' => [
        'received'        => true,
        'bank'            => 'BARB_R',
        'status'          => Status::FAILURE
    ],

    'testPaymentVerifySuccessEntity' => [
        'bank_payment_id' => '12345678',
        'received'        => true,
        'bank'            => 'BARB_R',
        'status'          => Status::SUCCESS
    ],

    'testAuthFailedEntity' => [
        'bank_payment_id' => '12345678',
        'received'        => true,
        'bank'            => 'BARB_R',
        'status'          => Status::SUCCESS
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
