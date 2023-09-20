<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testSplitPaymentWithFullAmountPositive' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
            'content' => [
                "order_id" => "order_MJ9HE9abhiPXmq",
                "currency" => "INR",
                "email" => "test@razorpay.com",
                "contact" => "9999999999",
                "card" => [
                    "number" => "4242424242424242",
                    "name" => "Test Card",
                    "expiry_month" => "12",
                    "expiry_year" => "2048",
                    "cvv" => "999"
                ],
                "amount" => 1000,
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],
    'testSplitPaymentWithFullAmountNegative' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
            'content' => [
                "order_id" => "order_MJ9HE9abhiPXmq",
                "currency" => "INR",
                "email" => "test@razorpay.com",
                "contact" => "9999999999",
                "card" => [
                    "number" => "4242424242424242",
                    "name" => "Test Card",
                    "expiry_month" => "12",
                    "expiry_year" => "2048",
                    "cvv" => "999"
                ],
                "amount" => 500,
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH,
        ]
    ],
    'testSplitPayment' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
            'content' => [
                "order_id" => "order_MJ9HE9abhiPXmq",
                "currency" => "INR",
                "email" => "test@razorpay.com",
                "contact" => "9999999999",
                "card" => [
                    "number" => "4242424242424242",
                    "name" => "Test Card",
                    "expiry_month" => "12",
                    "expiry_year" => "2048",
                    "cvv" => "999"
                ],
                "amount" => 900,
                "wallet_amount" => 100,
                "wallet_user_id" => "iuser_I9eCvXfHx7nzZF"
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],
    'testSplitPaymentWithAutoCapture' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
            'content' => [
                "order_id" => "order_MJ9HE9abhiPXmq",
                "currency" => "INR",
                "email" => "test@razorpay.com",
                "contact" => "9999999999",
                "card" => [
                    "number" => "4242424242424242",
                    "name" => "Test Card",
                    "expiry_month" => "12",
                    "expiry_year" => "2048",
                    "cvv" => "999"
                ],
                "amount" => 900,
                "wallet_amount" => 100,
                "wallet_user_id" => "iuser_I9eCvXfHx7nzZF"
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],
    'testSplitPaymentWithFeatureDisabled' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
            'content' => [
                "order_id" => "order_MJ9HE9abhiPXmq",
                "currency" => "INR",
                "email" => "test@razorpay.com",
                "contact" => "9999999999",
                "card" => [
                    "number" => "4242424242424242",
                    "name" => "Test Card",
                    "expiry_month" => "12",
                    "expiry_year" => "2048",
                    "cvv" => "999"
                ],
                "amount" => 900,
                "wallet_amount" => 100,
                "wallet_user_id" => "iuser_I9eCvXfHx7nzZF"
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testSplitPaymentWithIncorrectAmount' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
            'content' => [
                "order_id" => "order_MJ9HE9abhiPXmq",
                "currency" => "INR",
                "email" => "test@razorpay.com",
                "contact" => "9999999999",
                "card" => [
                    "number" => "4242424242424242",
                    "name" => "Test Card",
                    "expiry_month" => "12",
                    "expiry_year" => "2048",
                    "cvv" => "999"
                ],
                "amount" => 900,
                "wallet_amount" => 500,
                "wallet_user_id" => "iuser_I9eCvXfHx7nzZF"
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_SPLIT_PAYMENT_ORDER_AMOUNT_MISMATCH,
        ]
    ],
    'testSplitPaymentTimeout' => [
        'request' => [
            'url'    => '/payments/%s/timeout_new',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'retry_timeout' => false,
                'payment' => [
                    'status'        => 'failed',
                    'error_reason'  => 'payment_timed_out'
                ]
            ],
        ],
    ],
    'testSplitPaymentCancellation' => [
        'request' => [
            'url' => '/payments/:id/cancel',
            'method' => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CANCELLED_BY_USER
        ]
    ],
    'testSplitPaymentRetry' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
            'content' => [
                "order_id" => "order_MJ9HE9abhiPXmq",
                "currency" => "INR",
                "email" => "test@razorpay.com",
                "contact" => "9999999999",
                "card" => [
                    "number" => "4242424242424242",
                    "name" => "Test Card",
                    "expiry_month" => "12",
                    "expiry_year" => "2048",
                    "cvv" => "999"
                ],
                "amount" => 900,
                "wallet_amount" => 100,
                "wallet_user_id" => "iuser_I9eCvXfHx7nzZF"
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],
];
