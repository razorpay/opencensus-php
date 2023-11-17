<?php

return [
    'testFetchOrderDetailsForCheckout' => [
        'request' => [
            'method' => 'POST',
            'url' => '/internal/orders/checkout',
        ],
        'response' => [
            'content' => [
                'partial_payment' => false,
                'amount' => 50000,
                'currency' => 'INR',
                'amount_paid' => 0,
                'amount_due' => 50000,
                'first_payment_min_amount' => null,
            ],
        ],
    ],

    'testFetchTPVOrderDetailsForCheckout' => [
        'request' => [
            'method' => 'POST',
            'url' => '/internal/orders/checkout',
        ],
        'response' => [
            'content' => [
                'partial_payment' => false,
                'amount' => 50000,
                'currency' => 'INR',
                'amount_paid' => 0,
                'amount_due' => 50000,
                'first_payment_min_amount' => null,
                'bank' => 'FDRL',
                'account_number' => 'XXXXXXXXXXXXX40',
            ],
        ],
    ],

    'testFetchOrderDetailsForCheckoutWithAllPossibleFieldsInResponse' => [
        'request' => [
            'method' => 'POST',
            'url' => '/internal/orders/checkout',
        ],
        'response' => [
            'content' => [
                'partial_payment' => false,
                'amount' => 50000,
                'currency' => 'INR',
                'amount_paid' => 0,
                'amount_due' => 50000,
                'first_payment_min_amount' => null,
                'bank' => 'UTIB',
                'account_number' => 'XXXXXXXXXXXXX40',
                'method' => 'netbanking',
                'line_items_total' => 50000,
                'line_items' => [
                    [
                        'type' => 'e-commerce',
                        'sku' => '1g234',
                        'variant_id' => '12r34',
                        'other_product_codes' => [
                            'upc' => '12r34',
                            'ean' => '123r4',
                            'unspsc' => '123s4'
                        ],
                        'price' => '20000',
                        'offer_price' => '20000',
                        'tax_amount' => 0,
                        'quantity' => 1,
                        'name' => 'TEST',
                        'description' => 'TEST',
                        'weight' => '1700',
                        'dimensions' => [
                            'length' => '1700',
                            'width' => '1700',
                            'height' => '1700'
                        ],
                        'image_url' => 'http://url',
                        'product_url' => 'http://url',
                        'notes' => []
                    ],
                    [
                        'type' => 'e-commerce',
                        'sku' => '1g235',
                        'variant_id' => '12r34',
                        'other_product_codes' => [
                            'upc' => '12r34',
                            'ean' => '123r4',
                            'unspsc' => '123s4'
                        ],
                        'price' => '30000',
                        'offer_price' => '30000',
                        'tax_amount' => 0,
                        'quantity' => 1,
                        'name' => 'TEST',
                        'description' => 'TEST',
                        'weight' => 1700,
                        'dimensions' => [
                            'length' => 1700,
                            'width' => 1700,
                            'height' => 1700
                        ],
                        'image_url' => 'http://url',
                        'product_url' => 'http://url',
                        'notes' => []
                    ]
                ]
            ],
        ],
    ],

    'testFetchOrderDetailsForCheckoutWithExpandOrder' => [
        'request' => [
            'method' => 'POST',
            'url' => '/internal/orders/checkout',
            'content' => [
                'expand' => [
                    'order',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'account_number' => 'XXXXXXXXXXXXX40',
                'amount' => 50000,
                'amount_due' => 50000,
                'amount_paid' => 0,
                'bank' => 'UTIB',
                'currency' => 'INR',
                'first_payment_min_amount' => null,
                'line_items_total' => 50000,
                'line_items' => [
                    [
                        'name' => 'Line Item 1',
                        'price' => 10000,
                        'quantity' => 1,
                    ],
                    [
                        'name' => 'Line Item 2',
                        'price' => 20000,
                        'quantity' => 2,
                    ],
                ],
                'method' => 'netbanking',
                'order' => [
                    'id' => '', // Filled by the test
                    'account_number' => 'XXXXXXXXXXXXX40',
                    'amount' => 50000,
                    'amount_due' => 50000,
                    'amount_paid' => 0,
                    'app_offer' => false,
                    'attempts' => 0,
                    'authorized' => false,
                    'bank' => 'UTIB',
                    'checkout_config_id' => null,
                    'currency' => 'INR',
                    'customer_id' => null,
                    'discount' => false,
                    'first_payment_min_amount' => null,
                    'force_offer' => null,
                    'late_auth_config_id' => null,
                    'line_items_total' => 50000,
                    'merchant_id' => '10000000000000',
                    'method' => 'netbanking',
                    'notes' => [],
                    'offers' => [
                        'count' => 0,
                        'entity' => 'collection',
                        'items' => [],
                    ],
                    'offer_id' => null,
                    'order_metas' => [
                        [
                            'order_id' => '', // Filled by the test
                            'type' => 'one_click_checkout',
                            'value' => [
                                'line_items' => [
                                    [
                                        'name' => 'Line Item 1',
                                        'price' => 10000,
                                        'quantity' => 1,
                                    ],
                                    [
                                        'name' => 'Line Item 2',
                                        'price' => 20000,
                                        'quantity' => 2,
                                    ],
                                ],
                                'line_items_total' => 50000,
                            ],
                        ],
                    ],
                    'partial_payment' => false,
                    'payer_name' => 'ThisIsAwesome',
                    'payment_capture' => null,
                    'pg_router_synced' => 0,
                    'product_id' => null,
                    'product_type' => null,
                    'products' => [],
                    'provider_context' => null,
                    'public_key' => 'rzp_test_TheTestAuthKey',
                    'receipt' => 'R1',
                    'reference2' => null,
                    'reference3' => null,
                    'reference4' => null,
                    'reference5' => null,
                    'reference6' => null,
                    'reference7' => null,
                    'reference8' => null,
                    'status' => 'created',
                ],
                'partial_payment' => false,
            ],
        ],
    ],

    'testFetchOrderDetailsForCheckoutWithSubscriptionId' => [
        'request' => [
            'method' => 'POST',
            'url' => '/internal/orders/checkout',
            'content' => [
                'subscription_id' => '', // Filled by the TestCase
            ],
        ],
        'response' => [
            'content' => [
                'partial_payment' => false,
                'amount' => 50000,
                'currency' => 'INR',
                'amount_paid' => 0,
                'amount_due' => 50000,
                'first_payment_min_amount' => null,
            ],
        ],
    ],
];
