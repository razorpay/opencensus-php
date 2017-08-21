<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSuccessful13DigitPanTxn' => [
        'merchant_id' => "10000000000000",
        'amount' => 50000,
        'fee' => 1000,
        'service_tax' => 0,
        'tax' => 0,
        'pricing_rule_id' => null,
        'debit' => 0,
        'credit' => 49000,
        'currency' => "INR",
        'balance' => 1049000,
        'gateway_amount' => null,
        'gateway_fee' => 0,
        'gateway_service_tax' => 0,
        'api_fee' => 0,
        'gratis' => false,
        'fee_credits' => 0,
        'escrow_balance' => 0,
        'channel' => "kotak",
        'admin' => true,
    ]
];
