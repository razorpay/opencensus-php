<?php


namespace Functional\Merchant\helpers;


return [
    'testUploadMaxLimitViaFile' => [
        'request' => [
            'url' => '/merchant/payment_limit/update',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'max_payment_limit_data' => [
        [
            'merchant_id' => '38RR00000197367',
            'max_payment_amount' => 100000000000,
            'max_international_payment_amount' => 50000000,
        ],
        [
            'merchant_id' => 'H9sTmdNiFOOFCC',
            'max_payment_amoun' => 100000000000,
            'max_international_payment_amount' => 50000000,
        ],
        [
            'merchant_id' => '10000000000000',
            'max_payment_amount' => 60000000000,
            'max_international_payment_amount' => 50000000,
        ],
        [
            'merchant_id' => '38RR00000197367',
            'max_payment_amount' => 4200000000,
            'max_international_payment_amount' => 50000000,
        ],
        [
            'merchant_id' => '38RR00000197367',
            'max_payment_amount' => 2200000000,
            'max_international_payment_amount' => 50000000,
        ],
        [
            'merchant_id' => '38RR00000197367',
            'max_payment_amount' => 7000000000,
            'max_international_payment_amount' => 50000000,
        ],
        [
            'merchant_id' => '38RR00000197367',
            'max_payment_amount' => 3000000000,
            'max_international_payment_amount' => 50000000,
        ],
    ],

];
