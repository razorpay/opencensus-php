<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '9918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'cybersource',
        'terminal_id' => '1000CybrsTrmnl',
        'signed' => false,
        'verified' => null,
        'fee' => 1150,
        'service_tax' => 150,
        'entity' => 'payment',
    ],

    'testTransactionAfterCapture' => [
        'type' => 'payment',
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'fee' => 1150,
        'debit' => 0,
        'credit' => 48850,
        'currency' => 'INR',
        'balance' => 1048850,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'escrow_balance' => 1048850,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'reconciled_at' => null,
        'entity' => 'transaction',
        'admin' => true,
    ],


    'testPaymentCybersourceEntity' => [
        'amount' => 50000,
        'pares_status' => 'Y',
        'status' => 'captured',
        'entity' => 'cybersource',
    ],

    'testPaymentRefund' => [
        'commerce_indicator' => "Internet",
        'pares_status' => 'Y',
        'entity' => 'cybersource',
        'admin' => true,
    ],
];