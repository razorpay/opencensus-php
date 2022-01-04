<?php

namespace RZP\Tests\Functional\Merchant\Partner;

return [
    'testPartnerFUXDetailsAfterSignUp' => [
        'request'  => [
            'url'     => '/partner/first_user_experience',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'first_submerchant_added' => false,
                'first_earning_generated' => false,
                'first_commission_payout' => false,
                'api_integration' => false
            ]
        ]
    ],
    'testPartnerFUXDetailsAfterSubmerchantsAreAdded' => [
        'request'  => [
            'url'     => '/partner/first_user_experience',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'first_submerchant_added' => true,
                'first_earning_generated' => false,
                'first_commission_payout' => false,
                'api_integration' => false
            ]
        ]
    ],
    'testAggregatorPartnerFUXDetailsWhenIntegratedWithApi' => [
        'request'  => [
            'url'     => '/partner/first_user_experience',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'first_submerchant_added' => false,
                'first_earning_generated' => false,
                'first_commission_payout' => false,
                'api_integration' => true
            ]
        ]
    ],
    'testFullyManagedPartnerFUXDetailsWhenIntegratedWithApi' => [
        'request'  => [
            'url'     => '/partner/first_user_experience',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'first_submerchant_added' => false,
                'first_earning_generated' => false,
                'first_commission_payout' => false,
                'api_integration' => true
            ]
        ]
    ],
    'testPurePlatformPartnerFUXDetailsWhenIntegratedWithApi' => [
        'request'  => [
            'url'     => '/partner/first_user_experience',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'first_submerchant_added' => false,
                'first_earning_generated' => false,
                'first_commission_payout' => false,
                'api_integration' => true
            ]
        ]
    ],
    'testResellerPartnerFUXDetailsAfterEarningsAreGenerated' => [
        'request'  => [
            'url'     => '/partner/first_user_experience',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'first_submerchant_added' => true,
                'first_earning_generated' => true,
                'first_commission_payout' => false,
                'api_integration' => false
            ]
        ]
    ],
    'testAggregatorPartnerFUXDetailsAfterEarningsAreGenerated' => [
        'request'  => [
            'url'     => '/partner/first_user_experience',
            'method'  => 'GET',
            'content' => []
        ],
        'response' => [
            'content' => [
                'first_submerchant_added' => true,
                'first_earning_generated' => true,
                'first_commission_payout' => false,
                'api_integration' => false
            ]
        ]
    ]
];
