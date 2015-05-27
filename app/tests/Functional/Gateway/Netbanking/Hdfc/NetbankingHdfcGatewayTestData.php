<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

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
        'contact' => '9918899029',
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
        'amount' => 'amount',
        'bank' => 'HDFC',
        'client_code' => 'client_code',
        'merchant_code' => 'merchant_code',
        'bank_payment_id' => null,
        'error_message' => null,
        'entity' => 'netbanking',
    ],
];
