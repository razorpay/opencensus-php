<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id' => '10000000000000',
        'amount' => 50000,
        'method' => 'upi',
        'status' => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded' => 0,
        'refund_status' => null,
        'currency' => 'INR',
        'description' => 'random description',
        'bank' => null,
        'error_code' => null,
        'error_description' => null,
        'email' => 'a@b.com',
        'contact' => '9918899029',
        'notes' => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway' => 'upi_hdfc',
        'signed' => false,
        'verified' => null,
        'entity' => 'payment',
    ],

    'testPaymentUpiEntity' => [
        'action'                => 'authorize',
        'amount'                => 50000,
        'bank'                  => 'hdfc',
        'received'              => true,
        'email'                 => null,
        'contact'               => null,
        'gateway_merchant_id'   => '123456',
        'status_code'           => '0',
        'vpa'                   => 'shk@hdfc',
        'entity'                => 'upi',
    ],
];
