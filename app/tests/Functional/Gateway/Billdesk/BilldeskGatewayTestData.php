<?php

use EE\Error\ErrorCode;
use EE\Error\PublicErrorCode;
use EE\Error\PublicErrorDescription;
use Gateway\Hdfc;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'netbanking',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => NULL,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => 'HDFC',
        'error_code' => NULL,
        'error_description' => NULL,
        'email' => 'a@b.com',
        'contact' => '9918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'billdesk',
        'terminal_id' => '1000BdeskTrmnl',
        'signed' => false,
        'verified' => NULL,
        'entity' => 'payment',
    ],

    'testPaymentBilldeskEntity' => [
        'action'=> 'authorize',
        'BankID'=> 'HDFC',
        'CurrencyType'=> 'INR',
        'ItemCode'=> 'DIRECT',
        'TypeField1'=> 'R',
        'TypeField2'=> 'F',
        'RefAmount'=> NULL,
        'RefDateTime'=> NULL,
        'RefStatus'=> NULL,
        'RefundId'=> NULL,
        'ErrorCode'=> NULL,
        'ErrorReason'=> NULL,
        'ProcessStatus'=> NULL,
        'refund_id'=> NULL,
        'entity'=> 'billdesk',
    ],
];