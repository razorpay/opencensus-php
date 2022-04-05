<?php

use RZP\Error\ErrorCode;

return [
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
        ],
    ],

    'testSearchEsForRefundNotesExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'refund_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'refund_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'GOBUSANDe2c92f0f46',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testSearchEsForRefundNotesExpectedSearchParamsNotFound' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'refund_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'refund_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'CCPjoWzlDJG0g7',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testSearchEsForPaymentNotesExpectedSearchParams' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'REZDELKJe2c92f0f46',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testSearchEsForPaymentNotesExpectedSearchParamsNotFound' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'GOBUSANDe2c92f0f46',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testSearchEsForPaymentNotesExpectedSearchParamsAndNotFound' => [
        'index' => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'type'  => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
        'body'  => [
            '_source' => false,
            'from'    => 0,
            'size'    => 10,
            'query'   => [
                'bool' => [
                    'must' => [
                        [
                            'match' => [
                                'notes.value' => [
                                    'query' => 'CCPjoWzlDJG0g7',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
            'sort' => [
                '_score' => [
                    'order' => 'desc',
                ],
                'created_at' => [
                    'order' => 'desc',
                ],
            ],
        ],
    ],

    'testPaymentFetchDetailsForCustomerFromIdFetchFromUpiPayment' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'captured',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Payment was successfully settled to the Merchant',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromIdFetchFromUpiRefund' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'processed',
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed by Razorpay',
                                'tertiary_message'  => '',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayIdFailedPaymentCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'pending',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Payment request has been initiated by Razorpay',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                            'tertiary_message'  => ''
                            ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayIdPendingPaymentCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'pending',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Payment request has been initiated by Razorpay',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                            'tertiary_message'  => ''
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayIdCreatedPaymentCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'created',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Payment request has been initiated by Razorpay',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayIdLateAuthorizedCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'authorized',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Your payment was not successful',
                            'tertiary_message'  => '',
                            'late_auth'         => true,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayIdAuthorizedCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'authorized',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Your Payment was Successful from Razorpay\'s End',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayIdCapturedCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'captured',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Payment was successfully settled to the Merchant',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayIdLateAuthorizedCapturedCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'captured',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Payment was successfully settled to the Merchant',
                            'tertiary_message'  => '',
                            'late_auth'         => true,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'initiated',
                                'acquirer_data'     => [
                                'arn' => NULL,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Delayed',
                                'secondary_message' => 'The refund for ₹ 500 done on Test Merchant is being processed and is '.
                                    'taking longer than usual due to a technical issue at the bank\'s side.',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testVoidRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'initiated',
                                'acquirer_data'     => [
                                    'arn' => NULL,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Delayed',
                                'secondary_message' => 'The refund for ₹ 500 done on Test Merchant is being processed and is '.
                                    'taking longer than usual due to a technical issue at the bank\'s side.',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCaseForUSDPayment' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'initiated',
                                'acquirer_data'     => [
                                    'arn' => NULL,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Delayed',
                                'secondary_message' => 'The refund for $ 500 done on Test Merchant is being processed and is '.
                                    'taking longer than usual due to a technical issue at the bank\'s side.',
                                'tertiary_message'  => '',
                                'currency'          => 'USD',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'USD',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCaseTimePassed' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'initiated',
                                'acquirer_data'     => [
                                    'arn' => NULL,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Delayed',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testVoidRefundFetchDetailsForCustomerFromRazorpayIdFailedRefundCaseTimePassed' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'initiated',
                                'acquirer_data'     => [
                                    'arn' => NULL,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Delayed',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundWithArnCaseTimePassed' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'processed',
                                'acquirer_data'     => [
                                    'arn' => 'random_arn',
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed by Razorpay',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundWithArnCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'processed',
                                'acquirer_data'     => [
                                    'arn' => 'random_arn',
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed by Razorpay',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'payment_id' => 'dummy',
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'processed',
                                'acquirer_data'     => [
                                    'arn' => null,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed by Razorpay',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testVoidRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCase' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'payment_id' => 'dummy',
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'processed',
                                'acquirer_data'     => [
                                    'arn' => null,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCaseTimePassed' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'payment_id' => 'dummy',
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'processed',
                                'acquirer_data'     => [
                                    'arn' => null,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed by Razorpay',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testVoidRefundFetchDetailsForCustomerFromRazorpayIdProcessedRefundCaseTimePassed' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'payment_id' => 'dummy',
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [
                            [
                                'amount'            => 50000,
                                'status'            => 'processed',
                                'acquirer_data'     => [
                                    'arn' => null,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'refunded',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => '',
                            'secondary_message' => '',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testPaymentFetchDetailsForCustomerFromRazorpayOrderIdMultiplePaymentsMultipleRefunds' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'id'         => 'dummy',
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => ['payments' =>
                [
                    [
                        'refunds' => [],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'authorized',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Your payment was not successful',
                            'tertiary_message'  => '',
                            'late_auth'         => true,
                            'currency'          => 'INR',
                        ],
                    ],
                    [
                        'refunds' => [
                            [
                                'amount'            => 12500,
                                'status'            => 'initiated',
                                'acquirer_data'     => [
                                    'arn' => null,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Delayed',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ],
                            [
                                'amount'            => 25000,
                                'status'            => 'processed',
                                'acquirer_data'     => [
                                    'arn' => null,
                                ],
                                'merchant_name'     => 'Test Merchant',
                                'primary_message'   => 'Your Refund has been Processed by Razorpay',
                                'tertiary_message'  => '',
                                'currency'          => 'INR',
                            ]
                        ],
                        'payment' => [
                            'amount'            => 50000,
                            'status'            => 'captured',
                            'merchant_name'     => 'Test Merchant',
                            'primary_message'   => 'Payment was successfully settled to the Merchant',
                            'tertiary_message'  => '',
                            'late_auth'         => false,
                            'currency'          => 'INR',
                        ],
                    ]
                ]
            ]
        ]
    ],

    'testCustomerFetchIdNotFound' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => [
                'payments' => []
            ],
        ]
    ],

    'testCustomerFetchInvalidId' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'field'      => 'id',
                    'code'       => ErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testInstantFailedOnGatewayUnsupportedRefundMessage' => [
        'request' => [
            'method'  => 'GET',
            'url'     => '/customer/refunds',
            'content' => [
                'captcha'    => 'dummy',
                'mode'       => 'test',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],
];
