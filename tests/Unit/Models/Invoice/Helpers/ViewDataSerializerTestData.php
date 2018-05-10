<?php

return [
    'expectedSerializedInvoiceData' => [
        'environment'   => 'testing',
        'is_test_mode'  => false,
        'invoicejs_url' => 'https://cdn.razorpay.com/v1/invoice.js',
        'key_id'        => 'rzp__TheTestAuthKey',
        'merchant' => [
            'brand_color'      => 'rgb(35,113,236)',
            'brand_text_color' => '#ffffff',
            'image'            => null,
            'name'             => 'Test Merchant',
            'id'               => '10000000000000',
        ],
        'invoice' => [
            'id'                    => 'inv_1000000invoice',
            'entity'                => 'invoice',
            'invoice_number'        => null,
            'customer_id'           => 'cust_100000customer',
            'customer_details'      => [
                'name'             => 'test',
                'email'            => 'test@razorpay.com',
                'contact'          => '1234567890',
                'billing_address'  => null,
            ],
            'order_id'              => 'order_100000000order',
            'line_items'            => [],
            'payment_id'            => null,
            'status'                => 'issued',
            'expired_at'            => null,
            'amount'                => 100000,
            'amount_paid'           => 0,
            'amount_due'            => 100000,
            'currency'              => 'INR',
            'short_url'             => 'http://bitly.dev/2eZ11Vn',
            'type'                  => 'invoice',
            'is_paid'               => false,
            'callback_url'          => null,
            'callback_method'       => null,
            'payments'              => [],
            'amount_formatted'      => '1,000.00',
            'amount_due_formatted'  => '1,000.00',
            'amount_paid_formatted' => '0.00',
            // 'issued_at_formatted'   => '4 Dec 2017',
            // 'date_formatted'        => '4 Dec 2017',
            // 'expire_by_formatted'   => '6 Dec 2017',
            'expired_at_formatted'  => null,
        ],
    ],

    'expectedReplacedSerializedInvoiceWithPaymentsData' => [
        'invoice' => [
            'status'                => 'paid',
            'amount_paid'           => 100000,
            'amount_due'            => 0,
            'is_paid'               => true,
            'amount_paid_formatted' => '1,000.00',
            'amount_due_formatted'  => '0.00',
            'payments'              => [
                [
                    'amount'               => 1000000,
                    'status'               => 'created',
                    'method'               => 'card',
                    'formatted_amount'     => '₹ 10000',
                    // 'formatted_created_at' => '5 Dec 2017',
                ],
            ],
        ],
    ],

    'expectedReplacedSerializedSubscriptionInvoiceData' => [
        'invoice' => [
            'subscription_id' => 'sub_10subscription',
            'subscription' => [
                'id'         => 'sub_10subscription',
                'status'     => 'created',
            ],
        ],
    ],
];
