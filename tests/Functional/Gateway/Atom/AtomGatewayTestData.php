<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testNetbankingPaymentAuthorize' => [
        'entity' => 'payment',
        'notes' => [],
        'currency' => 'INR',
        'amount_refunded' => 0,
        'amount' => 50000,
        'status' => 'authorized',
        'refund_status' => null,
    ],

    'testNetbankingPaymentCapture' => [
        'entity' => 'payment',
        'notes' => [],
        'currency' => 'INR',
        'amount_refunded' => 0,
        'amount' => 50000,
        'status' => 'captured',
        'refund_status' => null,
    ],

    'testNBPaymentFailureAtBank' => [
        'request' => [
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\GatewayErrorException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
        'success' => false,
    ],

    'testNBPaymentOnSharedTerminal' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1476,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 48524,
        'currency' => 'INR',
        'balance' => 48524,
        'gateway_fee' => 0,
        'api_fee' => 0,
        //        'escrow_balance' => 1048562,
        'channel' => \RZP\Models\Settlement\Channel::AXIS,
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],

    'testTpvPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'SBIN',
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

    'testFailedVerifyMismatch' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED ,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\PaymentVerificationException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_VERIFICATION_FAILED,
        ],
    ],

    'testFailedPayment' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment didn't go through as it was declined by the bank. Try another payment method or contact your bank.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED
        ],
    ],

    'testInvalidResponse' =>[
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
            'internal_error_code' => ErrorCode::GATEWAY_ERROR_INVALID_RESPONSE
        ],
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank_name'       => 'SBIN',
        'status'          => 'Ok',
    ],

    'testPaymentNetbankingEntityForAllahabad' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank_name'       => 'ALLA',
        'status'          => 'Ok',
    ],

    'testPaymentRefund' => [
        'action'                     => 'refund',
        'error_code'                 => '00',
        'amount'                     => 50000,
        'entity'                     => 'atom',
        'gateway_result_description' => 'Full Refund initiated successfully',
    ],

    'testAllahabadTpvMigrationPayment' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'ALLA',
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

];
