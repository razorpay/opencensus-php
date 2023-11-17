<?php

return [
    'testGetInvoiceDetailsForCheckout' => [
        'request' => [
            'url'       => '/internal/invoices/checkout',
            'method'    => 'GET',
            'content'   => [],
        ],
        'response' => [
            'content'     => [
                'invoice' => [
                    'url' => 'http://bitly.dev/2eZ11Vn',
                    'amount' => 100000,
                ],
                'order' => [
                    'partial_payment' => false,
                    'amount' => 1000000,
                    'currency' => 'INR',
                    'amount_paid' => 0,
                    'amount_due' => 1000000,
                    'first_payment_min_amount' => null,
                ],
                'customer' => [
                    'id' => 'cust_100000customer',
                    'entity' => 'customer',
                    'name' => 'test',
                    'email' => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'gstin' => null,
                    'notes' => [],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetInvoiceDetailsForCheckoutByInvoiceIdInQueryParams' => [
        'request' => [
            'url'       => '/internal/invoices/checkout/randomId',
            'method'    => 'GET',
            'content'   => [],
        ],
        'response' => [
            'content'     => [
                'invoice' => [
                    'url' => 'http://bitly.dev/2eZ11Vn',
                    'amount' => 100000,
                ],
                'order' => [
                    'partial_payment' => false,
                    'amount' => 1000000,
                    'currency' => 'INR',
                    'amount_paid' => 0,
                    'amount_due' => 1000000,
                    'first_payment_min_amount' => null,
                ],
                'customer' => [
                    'id' => 'cust_100000customer',
                    'entity' => 'customer',
                    'name' => 'test',
                    'email' => 'test@razorpay.com',
                    'contact' => '1234567890',
                    'gstin' => null,
                    'notes' => [],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testGetInvoiceDetailsForCheckoutBySubscriptionId' => [
        'request' => [
            'url'       => '/internal/invoices/checkout/randomId',
            'method'    => 'GET',
            'content'   => [],
        ],
        'response' => [
            'content'     => [
                'invoice' => [
                    'url' => 'http://bitly.dev/2eZ11Vn',
                    'amount' => 100000,
                ],
            ],
            'status_code' => 200,
        ],
    ],
];
