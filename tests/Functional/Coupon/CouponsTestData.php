<?php

return [
    'testCreateCoupon' => [
        'request' => [
            'content' => [
                'coupon_code' => 'RANDOM_123'
            ],
            'url'    => '/coupons',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity_type' => 'promotion',
                'coupon_code' => 'RANDOM_123'
            ]
        ]
    ],

    'testGetCouponsByPromotionId' => [
        'request' => [
            'content' => [

            ],
            'url'    => '/coupons',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'entity_type' => 'promotion',
                        'coupon_code' => 'RANDOM'
                    ]
                ]
            ]
        ]
    ],
];
