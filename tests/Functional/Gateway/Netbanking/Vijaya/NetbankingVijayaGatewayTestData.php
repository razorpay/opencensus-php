<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testPayment' => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'netbanking',
        'status'            => 'captured',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'card_id'           => null,
        'bank'              => 'VIJB',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'netbanking_vijaya',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'terminal_id'       => '100NbVijbTrmnl',
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => '12345678',
        'received'        => true,
        'bank'            => 'VIJB',
        'status'          => 'Y',
    ],
];
