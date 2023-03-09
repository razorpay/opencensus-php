<?php

return [
    'testPayment'           => [
        'merchant_id'       => '10000000000000',
        'amount'            => 50000,
        'method'            => 'wallet',
        'status'            => 'authorized',
        'amount_authorized' => 50000,
        'amount_refunded'   => 0,
        'refund_status'     => null,
        'currency'          => 'INR',
        'description'       => 'random description',
        'bank'              => null,
        'wallet'            => 'phonepe',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
            'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'wallet_phonepe',
        'terminal_id'       => '1ShrdPhnepeTrm',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'otp_attempts'      => null
    ],
];
