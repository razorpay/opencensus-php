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
    'testHdfcCheckoutHitWithCCText' => [
        'request'  => [
            'url'    => '/checkout/embedded',
            'method' => 'POST',
            'content' => [
                'receiver_types' => 'qr_code',
                'key_id' => 'rzp_live_tDseCX0eo6he8T',
                'order_id' => 'order_PDHE9FEX1vAlDG',
                'method' => [
                    'smartcollect' => true
                ]
            ],
        ],
        'response' => [
            'content' => [
                'type'     => 'hdfc',
            ],
        ],
    ],
    'testHdfcCheckoutDisabledByFeatureFlag' => [
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
