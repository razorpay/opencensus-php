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
                "failures" => []
            ]
        ]
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
