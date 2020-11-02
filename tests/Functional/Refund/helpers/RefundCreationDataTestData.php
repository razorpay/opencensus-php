<?php

return [
    'fetchRefundCreationData' => [
        'request' => [
            'method'  => 'get',
            'url'     => '/refunds/fetch_creation_data',
            'content' => [
                'payment_id' => 'pay_FQDVeqyAswpKBX',
                'amount' => 100,
            ],
        ],
        'response' => [
            'content' => []
        ],
    ],
];
