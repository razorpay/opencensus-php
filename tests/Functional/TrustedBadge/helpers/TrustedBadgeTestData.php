<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testTrustedBadgeDetailsWithEntry' => [
        'request' => [
            'content' => [],
            'url'    => '/trusted_badge',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "status"            => 'eligible',
                "merchant_status"   => '',
                "is_delisted_atleast_once" => 0,
                'is_live'           => true,
            ]
        ]
    ],
    'testTrustedBadgeDetailsWithoutEntry' => [
        'request' => [
            'content' => [],
            'url'    => '/trusted_badge',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                "status"            => 'ineligible',
                "merchant_status"   => '',
                "is_delisted_atleast_once" => 0,
                'is_live'           => false,
            ]
        ]
    ],
    'testAddToTrustedBadgeBlacklist' => [
        'request' => [
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000001'],
                'status' =>  'blacklist',
                'action' => 'add',
            ],
            'url' => '/trusted_badge/status',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'success' => 1,
                'failures' => ['10000000000001']
            ]
        ]
    ],
    'testRemoveFromTrustedBadgeBlacklist' => [
        'request' => [
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000001'],
                'status' =>  'blacklist',
                'action' => 'remove',
            ],
            'url' => '/trusted_badge/status',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'success' => 1,
                'failures' => ['10000000000001']
            ]
        ]
    ],
    'testAddToTrustedBadgeWhitelist' => [
        'request' => [
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000001'],
                'status' => 'whitelist',
                'action' => 'add',
            ],
            'url' => '/trusted_badge/status',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'success' => 1,
                'failures' => ['10000000000001']
            ]
        ]
    ],
    'testRemoveFromTrustedBadgeWhitelist' => [
        'request' => [
            'content' => [
                'merchant_ids' => ['10000000000000', '10000000000001'],
                'status' =>  'whitelist',
                'action' => 'remove',
            ],
            'url' => '/trusted_badge/status',
            'method' => 'PUT',
        ],
        'response' => [
            'content' => [
                'success' => 1,
                'failures' => ['10000000000001']
            ]
        ]
    ],
    'testIsDelistedAtleastOnce' => [
        'request' => [
            'content' => [],
            'url'     => '/trusted_badge',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                "status"            => 'ineligible',
                "merchant_status"   => 'waitlist',
                "is_delisted_atleast_once" => 1,
                'is_live'           => false,
            ]
        ]
    ],

    'testTrustedBadgeBlacklistWithStatusCheck' => [
        'request' => [
            'content' => [],
            'url'     => '/trusted_badge',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                "status"            => 'ineligible',
                "merchant_status"   => 'optout',
                "is_delisted_atleast_once" => 1,
                'is_live'           => false,
            ]
        ]
    ],

    'testTrustedBadgeWhitelistWithStatusCheck' => [
        'request' => [
            'content' => [],
            'url'     => '/trusted_badge',
            'method'  => 'GET'
        ],
        'response' => [
            'content' => [
                "status"            => 'ineligible',
                "merchant_status"   => 'optout',
                "is_delisted_atleast_once" => 1,
                'is_live'           => false,
            ]
        ]
    ],
    'testUpdateMerchantStatusWithWrongStatus' => [
        'request' => [
            'content' => [
                'merchant_status' => 'blacklist'
            ],
            'url'     => '/trusted_badge/merchant_status',
            'method'  => 'PUT'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Merchant status for Razorpay trusted badge',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_TRUSTED_BADGE_MERCHANT_STATUS
        ],
    ]
];
