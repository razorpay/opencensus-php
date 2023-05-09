<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'netbanking',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'card_id' => null,
        'bank' => 'HDFC',
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'netbanking_hdfc',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testPaymentNetbankingEntity' => [
        'action' => 'authorize',
        'amount' => 500,
        'bank' => 'HDFC',
        'received' => true,
        'client_code' => 'abcom',
        'merchant_code' => 'RAZORPAY2',
//        'bank_payment_id' => null,
        'error_message' => null,
        'entity' => 'netbanking',
    ],

    'testPaymentOnSharedTerminal' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'netbanking',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'card_id' => null,
        'bank' => 'HDFC',
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'netbanking_hdfc',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
        'terminal_id' => '100NbHdfcTrmnl',
    ],

    'testPaymentVerifySuccessEntity' => [
        'received' => true,
        'bank'     => 'HDFC',
    ],

    'testAmountTampering' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::SERVER_ERROR,
                    'description'   => PublicErrorDescription::SERVER_ERROR,
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\LogicException',
            'internal_error_code'   => ErrorCode::SERVER_ERROR_NBPLUS_PAYMENT_SERVICE_FAILURE,
        ],
    ],
];
