<?php

return [
    'testToArrayHosted' => [
        'id'               => 'inv_1000000invoice',
        'entity'           => 'invoice',
        'invoice_number'   => null,
        'customer_id'      => 'cust_100000customer',
        'customer_details' => [
            'name'             => 'test',
            'email'            => 'test@razorpay.com',
            'contact'          => '1234567890',
            'billing_address'  => null,
            'customer_name'    => 'test',
            'customer_email'   => 'test@razorpay.com',
            'customer_contact' => '1234567890',
        ],
        'order_id'         => 'order_100000000order',
        'payment_id'       => null,
        'status'           => 'issued',
        'issued_at'        => null,
        'expired_at'       => null,
        'date'             => null,
        'amount'           => 100000,
        'amount_paid'      => 0,
        'amount_due'       => 1000000,
        'currency'         => 'INR',
        'short_url'        => 'http://bitly.dev/2eZ11Vn',
        'type'             => 'invoice',
    ],
];
