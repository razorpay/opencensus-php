<?php

return [
    'testGetFormattedInvoiceData' => [
        'invoice'  => [
            'order_id'        => 'order_100000000order',
            'url'             => 'http://bitly.dev/2eZ11Vn',
            'amount'          => 100000,
        ],
        'order' => [
            'partial_payment' => false,
            'amount'          => 100000,
            'amount_paid'     => 0,
            'amount_due'      => 100000,
        ],
        'customer' => [
            'id'              => 'cust_100000customer',
            'entity'          => 'customer',
            'name'            => 'test',
            'email'           => 'test@razorpay.com',
            'contact'         => '1234567890',
            'notes'           => [],
        ],
    ],
];
