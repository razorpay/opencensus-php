<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'card',
        'status' => 'captured',
        'two_factor_auth' => 'passed',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => NULL,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => null,
        'error_code' => NULL,
        'error_description' => NULL,
        'email' => 'a@b.com',
        'contact' => '+919918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'axis_genius',
        'terminal_id' => '1000AxisGenius',
        'signed' => false,
        'verified' => NULL,
        'entity' => 'payment',
    ],
];
