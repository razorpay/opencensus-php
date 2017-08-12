<?php

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
        'bank'              => 'CORP',
        'error_code'        => null,
        'error_description' => null,
        'email'             => 'a@b.com',
        'contact'           => '+919918899029',
        'notes'             => [
        'merchant_order_id' => 'random order id',
        ],
        'gateway'           => 'netbanking_corporation',
        'signed'            => false,
        'verified'          => null,
        'entity'            => 'payment',
        'terminal_id'       => '100NbCorpTrmnl',
    ],

    'testPaymentNetbankingEntity' => [
        'bank_payment_id' => '99999999',
        'received'        => true,
        'bank'            => 'CORP',
        'status'          => 'Y',
    ],
];
