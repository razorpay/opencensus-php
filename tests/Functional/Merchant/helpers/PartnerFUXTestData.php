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
                'api_integration' => false,
                'first_submerchant_accept_payments' => false,
                'partner_migration_enabled' => true
            ]
        ]
    ],
    'testPartnerFUXDetailsWithPartnerMigrationFlagDisabled' => [
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
                'api_integration' => false,
                'first_submerchant_accept_payments' => false,
                'partner_migration_enabled' => false
            ]
        ]
    ],
    'testPartnerFUXDetailsWithPartnershipServiceError' => [
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
                'api_integration' => false,
                'first_submerchant_accept_payments' => false,
                'partner_migration_enabled' => false
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
                'api_integration' => false,
                'first_submerchant_accept_payments' => false
            ]
        ]
    ],
    'testPartnerFUXDetailsAfterSubmerchantsAreLive' => [
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
                'api_integration' => false,
                'first_submerchant_accept_payments' => true
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
                'api_integration' => true,
                'first_submerchant_accept_payments' => false
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
                'api_integration' => true,
                'first_submerchant_accept_payments' => false
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
                'api_integration' => true,
                'first_submerchant_accept_payments' => false
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
                'api_integration' => false,
                'first_submerchant_accept_payments' => true
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
                'api_integration' => false,
                'first_submerchant_accept_payments' => true
            ]
        ]
    ]
];
