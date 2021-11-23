<?php

return [
    'testOnyxWillNotAllowInlineJavascriptInContentKeys' => [
        'GET' => [
            'request' => [
                'url' => 'https://example.com',
                'content' => [
                    'javascript:alert("You Have Been Hacked !!")' => 'Value1',
                    'Key2' => 'Value2',
                ],
                'method' => 'POST',
            ],
            'options' => json_encode([
                'key' => 'rzp_test_1DP5mmOlF5G5ag',
                'amount' => 100,
            ]),
            'back' => 'https://example.com',
        ],
        'POST' => [
            'razorpay_payment_id' => 'pay_abcde',
        ],
    ],
    'testOnyxWillNotAllowInlineJavascriptInContentValues' => [
        'GET' => [
            'request' => [
                'url' => 'https://example.com',
                'content' => [
                    'Key1' => 'javascript:alert("You Have Been Hacked !!")',
                    'Key2' => 'Value2',
                ],
                'method' => 'POST',
            ],
            'options' => json_encode([
                'key' => 'rzp_test_1DP5mmOlF5G5ag',
                'amount' => 100,
            ]),
            'back' => 'https://example.com',
        ],
        'POST' => [
            'razorpay_payment_id' => 'pay_abcde',
        ],
    ],
];
