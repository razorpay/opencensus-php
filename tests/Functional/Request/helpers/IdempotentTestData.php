<?php

namespace RZP\Tests\Functional\Invoice;

return [

    'testCreateInvoiceWithIdempotentKey' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'server' => [],
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],

    'testCreateInvoiceWithIdempotentKeyInOneRequest' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'server' => [],
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],

    'testCreateInvoiceWithIdempotentKeyAndPrivateAuth' => [
        'request' => [
            'url' => '/invoices',
            'method' => 'post',
            'server' => [],
            'content' => [
                'receipt'       => '00000000000001',
                'customer'      => [
                    'email'     => 'test@razorpay.com',
                    'contact'   => '9999999999',
                    'name'      => 'test',
                    'gstin'     => '29ABCDE1234L1Z1',
                ],
                'line_items'    => [
                    [
                        'name'          => 'Some item name',
                        'description'   => 'Some item description',
                        'amount'        => 100000,
                        'hsn_code'      => '00110022'
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'customer_details' => [
                    'email'   => 'test@razorpay.com',
                    'contact' => '9999999999',
                    'name'    => 'test',
                    'gstin'   => '29ABCDE1234L1Z1',
                ],
                'line_items' => [
                    [
                        'name'        => 'Some item name',
                        'description' => 'Some item description',
                        'amount'      => 100000,
                        'quantity'    => 1,
                        'type'        => 'invoice',
                        'hsn_code'    => '00110022'
                    ]
                ],
                'status'       => 'issued',
                'sms_status'   => 'pending',
                'email_status' => 'pending',
                'view_less'    => true,
                'amount'       => 100000,
                'currency'     => 'INR',
                'payment_id'   => null,
                'type'         => 'invoice',
            ],
        ],
    ],

];
