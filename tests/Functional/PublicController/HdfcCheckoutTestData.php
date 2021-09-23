<?php

return [
    'testHdfcCheckoutHit' => [
        'request'  => [
            'url'    => '/checkout/embedded',
            'method' => 'POST',
            'content' => [
                'receiver_types' => 'qr_code'
            ],
        ],
        'response' => [
            'content' => [
                'type'     => 'hdfc',
            ],
        ],
    ],
    'testHdfcCheckoutNotHit' => [
        'request'  => [
            'url'    => '/checkout/embedded',
            'method' => 'POST',
            'content' => [
                'receiver_types' => 'qr_code'
            ],
        ],
        'response' => [
            'content' => [
                'type'     => 'not_hdfc',
            ],
        ],
    ],
];
