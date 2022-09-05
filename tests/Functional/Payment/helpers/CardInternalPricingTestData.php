<?php

return [
    'testPricingFeeForRearchPayment' => [
        'request' => [
            'method' => 'get',
            'content' => []
        ],
        'response' => [
            'content' => [
                "fees" => 1000,
                "tax" => 00,
                "fee_bearer" => "platform",
                "currency" => "INR"
            ],
        ],
    ],
    'testPricingFeeCustomerFeeBearerRearchPayment' => [
        'request' => [
            'method' => 'get',
            'content' => []
        ],
        'response' => [
            'content' => [
                "fees" => 1000,
                "razorpay_fee" => 1000,
                "tax" => 0,
                "original_amount" => 50000,
                "amount" => 51000,
                "currency" => "INR",
                "fee_bearer" => "customer"
            ],
        ],
    ]
];
