<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateReward' => [
        'request' => [
            'content' => [
                "merchant_ids"  => ['10000000000000'],
                "reward"        => [
                    'name'                => 'Test Reward',
                    'advertiser_id'       => '100000Razorpay',
                    'percent_rate'        => 1000,
                    'starts_at'           => Carbon::tomorrow()->getTimestamp(),
                    'ends_at'             => Carbon::now()->addDays(2)->getTimestamp(),
                    'display_text'        => 'Some more details',
                    'terms'               => 'Some more details',
                    'percent_rate'        => 1000,
                    'max_cashback'        => 200,
                    'coupon_code'         => 'coupon_code',
                ],
            ],
            'url'    => '/rewards',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "success"  => 1,
                "failures" => [],
                "uniqueCouponResponse" => []
            ]
        ]
    ],
    'testCreateRewardWithUniqueCoupons' => [
        'request' => [
            'content' => [
                "merchant_ids"  => ['10000000000000'],
                "reward"        => [
                    'name'                => 'Test Reward',
                    'advertiser_id'       => '100000Razorpay',
                    'percent_rate'        => 1000,
                    'starts_at'           => Carbon::tomorrow()->getTimestamp(),
                    'ends_at'             => Carbon::now()->addDays(2)->getTimestamp(),
                    'display_text'        => 'Some more details',
                    'terms'               => 'Some more details',
                    'percent_rate'        => 1000,
                    'max_cashback'        => 200,
                    'coupon_code'         => 'coupon_code',
                    'unique_coupon_codes' => ['coup1','coup2','coup3']
                ],
            ],
            'url'    => '/rewards',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "success"  => 1,
                "failures" => [],
                "uniqueCouponResponse" => [
                    'failed_coupons_count'          => 0,
                    'failed_coupons'                => [],
                    'unique_coupon_available_count' => 3
                ]
            ]
        ]
    ],
    'testUpdateReward' => [
        'request' => [
            'content' => [
                "merchant_ids"  => ['10000000000000'],
                "reward"        => [
                    'name'                => 'Updated Reward Name',
                    'display_text'         => 'Extra Flat 15% off on Rs.499 or more',
                    'logo'                => 'Updated logo',
                    'terms'               => 'Updated Terms',
                    'coupon_code'         => 'updated_coupon_code',
                    'merchant_website_redirect_link' => 'https:\/\/bewakoof.app.link'
                ],
            ],
            'url'    => '/rewards/update',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'failed_merchant_ids' => [],
                'reward' => [
                    'entity'               => 'reward',
                    'name'                 => 'Updated Reward Name',
                    'display_text'         => 'Extra Flat 15% off on Rs.499 or more',
                    'coupon_code'          => 'updated_coupon_code',
                    'logo'                 => 'Updated logo',
                    'terms'                => 'Updated Terms',
                    'merchant_website_redirect_link' => 'https:\/\/bewakoof.app.link'
                ],
                'uniqueCouponResponse' => []
            ]
        ]
    ],
    'testUpdateRewardWithUniqueCoupons' => [
        'request' => [
            'content' => [
                "merchant_ids"  => ['10000000000000'],
                "reward"        => [
                    'name'                => 'Updated Reward Name',
                    'display_text'         => 'Extra Flat 15% off on Rs.499 or more',
                    'logo'                => 'Updated logo',
                    'terms'               => 'Updated Terms',
                    'coupon_code'         => 'updated_coupon_code',
                    'merchant_website_redirect_link' => 'https:\/\/bewakoof.app.link',
                    'unique_coupon_codes' => ['coup1','coup2','coup3']
                ],
            ],
            'url'    => '/rewards/update',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'failed_merchant_ids' => [],
                'reward' => [
                    'entity'               => 'reward',
                    'name'                 => 'Updated Reward Name',
                    'display_text'         => 'Extra Flat 15% off on Rs.499 or more',
                    'coupon_code'          => 'updated_coupon_code',
                    'logo'                 => 'Updated logo',
                    'terms'                => 'Updated Terms',
                    'merchant_website_redirect_link' => 'https:\/\/bewakoof.app.link'
                ],
                'uniqueCouponResponse' => [
                    'failed_coupons_count'          => 0,
                    'failed_coupons'                => [],
                    'unique_coupon_available_count' => 3
                ]
            ]
        ]
    ],
    'testUpdateRewardWithWrongStartTime' => [
        'request' => [
            'content' => [
                "merchant_ids"  => ['10000000000000'],
                "reward"        => [
                    'name'                => 'Updated Reward Name',
                    'display_text'         => 'Extra Flat 15% off on Rs.499 or more',
                    'logo'                => 'Updated logo',
                    'terms'               => 'Updated Terms',
                    'coupon_code'         => 'updated_coupon_code',
                    'merchant_website_redirect_link' => 'https:\/\/bewakoof.app.link'
                ],
            ],
            'url'    => '/rewards/update',
            'method' => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Updated Start time and current start time should be Later than Current Time',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_START_TIME
        ],
    ],
    'testActivateReward' => [
        'request' => [
            'content' => [
                'activate' => true,
            ],
            'url'    => '/rewards',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'status' => 'live',
            ]
        ]
    ],

    'testDeactivateReward' => [
        'request' => [
            'content' => [
                'activate' => 0,
            ],
            'url'    => '/rewards',
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'status' => 'available',
            ]
        ]
    ],

    'testDeleteReward' => [
        'request' => [
            'content' => [],
            'url'    => '',
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'success' => true,
            ]
        ]
    ],
    'testGetNullAdvertiserLogo' => [
        'request' => [
            'content' => [],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'logo_url' => null
            ]
        ]
    ],
    'testGetAdvertiserLogo' => [
        'request' => [
            'content' => [],
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                'logo_url' => 'https://dummycdn.razorpay_original.com/advertiser_logo_url'
            ]
        ]
    ],
    'testActivateRewardWithWrongStatus' => [
        'request' => [
            'content' => [
                'activate' => true,
            ],
            'url'    => '/rewards',
            'method' => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant Reward not found',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_REWARD_ACTIVATE
        ],
    ],

    'testDeactivateRewardWithWrongStatus' => [
        'request' => [
            'content' => [
                'activate' => 0,
            ],
            'url'    => '/rewards',
            'method' => 'PATCH'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant Reward not found',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_REWARD_DEACTIVATE
        ],
    ],
];
