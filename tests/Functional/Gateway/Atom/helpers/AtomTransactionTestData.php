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
        'credit' => 48562,
        'fee' => 1438,
        'gateway_fee' => 1438,
        'api_fee' => 0,
        'balance' => 1048562,
        'escrow_balance' => 1048562,
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
        'balance' => 998562,
        'escrow_balance' => 998562,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'atom',
    ],

    'txnDataAfterPaymentOnSharedTerminal' => [
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1438,
        'gateway_fee' => 1006,
        'api_fee' => 432,
        'service_tax' => 188,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 48562,
        'currency' => 'INR',
        'balance' => 48562,
        'escrow_balance' => 1048562,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],
];
