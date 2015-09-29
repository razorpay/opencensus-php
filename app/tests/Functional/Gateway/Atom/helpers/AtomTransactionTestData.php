<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;

return [
    'txnDataAfterCapturingAtomPayment' => [
        'entity' => 'transaction',
        'type' => 'payment',
        'amount' => 50000,
        'currency' => 'INR',
        'debit' => 0,
        'credit' => 48575,
        'fee' => 1425,
        'gateway_fee' => 1425,
        'api_fee' => 0,
        'balance' => 1048575,
        'escrow_balance' => 1048575,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
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
        'balance' => 998575,
        'escrow_balance' => 998575,
        'merchant_id' => '10000000000000',
        'pricing_rule_id' => null,
        'channel' => 'atom',
    ],

    'txnDataAfterPaymentOnSharedTerminal' => [
        'type' => 'payment',
        'amount' => 50000,
        'fee' => 1425,
        'gateway_fee' => 998,
        'api_fee' => 427,
        'pricing_rule_id' => '1zD0BXpeOyaqpB',
        'debit' => 0,
        'credit' => 48575,
        'currency' => 'INR',
        'balance' => 48575,
        'escrow_balance' => 1048575,
        'channel' => 'kotak',
        'settled' => false,
        'settlement_id' => null,
        'entity' => 'transaction',
    ],
];