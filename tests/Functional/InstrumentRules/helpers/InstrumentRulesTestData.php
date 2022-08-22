<?php

return [
    'testMerchantOnSavedEvent' => [
        'request' => [
            'content' => [
                'category' => '5423',
            ],
            'url'    => '/merchants/10000000000016',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'id'   => '10000000000016',
                'category' => '5423',
            ],
        ],
    ],

    'testMerchantDetailOnSavedEvent' => [
        'request'  => [
            'content' => [
                'business_category'                        => 'financial_services',
                'business_subcategory'                     => 'lending',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_category'                        => 'financial_services',
                'business_subcategory'                     => 'lending',
            ],
        ],
    ],

    'testMerchantDetailOnSavedEventWithruleBasedFeatureFlag' => [
        'request'  => [
            'content' => [
                'business_category'                        => 'financial_services',
                'business_subcategory'                     => 'lending',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_category'                        => 'financial_services',
                'business_subcategory'                     => 'lending',
            ],
        ],
    ],

    'testMerchantManualTriggerEventThrowsException' => [
        'request'  => [
            'content' => [
                'merchant_ids' => ["10000000000016"],
            ],
            'url'     => '/instrument_rules/events/trigger',
            'method'  => 'POST',
        ],
        'response' => [
              'content' => [
                  'failed_ids' => ["10000000000016"],
                  'success_ids' => [],
              ],
        ],
    ],

    'testMerchantManualTriggerEventThrowsExceptionWithRuleBasedFeatureFlag' => [
        'request'  => [
            'content' => [
                'merchant_ids' => ["10000000000016"],
            ],
            'url'     => '/instrument_rules/events/trigger',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'failed_ids' => ["10000000000016"],
                'success_ids' => [],
            ],
        ],
    ],
];
