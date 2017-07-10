<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'txnDataAfterCapturingAtomPayment' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 48525,
        'fee' => 1475,
        'gateway_fee' => 1475,
        'api_fee' => 0,
        'balance' => 1048525,
//        'escrow_balance' => 1048562,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'atom',
    ],

    'txnDataAfterRefundingAtomPayment' => [
        'entity' => 'transaction',
        'type' => 'refund',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 50000,
        'credit' => 0,
        'fee' => 0,
        'gateway_fee' => 0,
        'api_fee' => 0,
        'balance' => 998525,
        // 'escrow_balance' => 998562,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'atom',
    ],

    'txnDataAfterPaymentOnSharedTerminal' => [
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1475,
        'gateway_fee' => 1033,
        'api_fee' => 442,
        'service_tax' => 225,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 48525,
        'currency' => 'INR',
        'balance' => 48525,
        // 'escrow_balance' => 1048562,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],
];
