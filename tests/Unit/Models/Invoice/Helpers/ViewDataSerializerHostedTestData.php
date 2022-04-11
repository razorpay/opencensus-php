<?php

return [
    'expectedSerializedInvoiceData' => [
        'environment'   => 'testing',
        'is_test_mode'  => false,
        'invoicejs_url' => 'https://cdn.razorpay.com/v1/invoice.js',
        'key_id'        => 'rzp__TheTestAuthKey',
        'merchant' => [
            'id'                               => '10000000000000',
            'name'                             => 'Test Merchant',
            'image'                            => null,
            'brand_color'                      => 'rgb(35,113,236)',
            'brand_text_color'                 => '#ffffff',
            'cin'                              => null,
            'gstin'                            => null,
            'has_cin_or_gstin'                 => false,
            'business_registered_address_text' => null,
        ],
        'invoice' => [
            'id'                    => 'inv_1000000invoice',
            'entity'                => 'invoice',
            'invoice_number'        => null,
            'customer_id'           => 'cust_100000customer',
            'customer_details'      => [
                'name'                         => 'test',
                'email'                        => 'test@razorpay.com',
                'contact'                      => '1234567890',
                'billing_address'              => null,
                'shipping_address'             => null,
                'billing_address_text'         => null,
                'shipping_address_text'        => null,
            ],
            'order_id'              => 'order_100000000order',
            'line_items'            => [],
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
            'supply_state_name'     => null,
            'merchant_gstin'        => null,
            'merchant_label'        => 'Test Merchant',
        ],
    ],

    'expectedReplacedSerializedInvoiceWithTaxesAndCustomerAddresses' => [
        'merchant' => [
            'business_registered_address_text' => 'Line 1, Bangalore, India',
        ],
        'invoice' => [
            'customer_details' => [
                'name'                         => '',
                'email'                        => '',
                'contact'                      => '',
                'billing_address' => [
                    'type'    => 'billing_address',
                    'line1'   => 'billing address line 1',
                    'line2'   => 'some line two',
                    'country' => 'in',
                ],
                'shipping_address' => [
                    'type'    => 'shipping_address',
                    'line1'   => 'some line one',
                    'line2'   => 'some line two',
                    'country' => 'in',
                ],
                'billing_address_text' => "billing address line 1\nsome line two\nBangalore, Karnataka, India - 560078",
                'shipping_address_text' => "some line one\nsome line two\nBangalore, Karnataka, India - 560078",
            ],
            'supply_state_name'  => 'Bihar',
            'merchant_gstin'     => '29kjsngjk213922',
            'has_address_or_pos' => true,
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
            'customer_details'      => '',
            'payments'              => [
                [
                    'amount'               => 1000000,
                    'status'               => 'captured',
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
