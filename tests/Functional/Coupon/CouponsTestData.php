<?php

$defaultRequestAndResponse = [
        'request' => [
            'content' => [
                'coupon_code' => 'RANDOM-123'
            ],
            'url'    => '/coupons',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity_type' => 'promotion',
                'coupon_code' => 'RANDOM-123'
            ]
        ]
    ];

return [
    'testCreateCoupon' => $defaultRequestAndResponse,

    'testCreateCouponAndApplyOnMerchant' => $defaultRequestAndResponse,

    'testMultiCouponApply' => $defaultRequestAndResponse,

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

    'testDeleteCoupon' => [
        'request' => [
            'content' => [

            ],
            'url'    => '/coupons',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'entity_type' => 'promotion',
                'coupon_code' => 'RANDOM'
            ]
        ]
    ],

    'testApplyOnetimeCoupon' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'coupon_code'  => 'RANDOM'
            ],
            'url'    => '/coupons/apply',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ]
    ],

    'testApplyRecurringCoupon' => [
        'request' => [
            'content' => [
                'merchant_id' => '10000000000000',
                'coupon_code'  => 'RANDOM'
            ],
            'url'    => '/coupons/apply',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ]
    ],
];
