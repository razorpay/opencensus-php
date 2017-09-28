<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\Feature\Constants;

return [
    'addFeatures' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [
                'names'       => ['dummy'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'deleteFeature' => [
        'request'  => [
            'url'     => "/features/10000000000000/dummy",
            'method'  => 'delete',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
            'content' => [ ]
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'updateFeatureAsMerchant' => [
        'request' => [
            'content' => [
                'features'      => [ ],
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'verifyFeatureAbsence' => [
        'request' => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'verifyFeaturePresence' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'all_features'      => [
                    'dummy',
                    'webhooks',
                    'aggregator',
                    'tokens',
                    's2swallet',
                    's2supi',
                    's2saeps',
                    'setl_report',
                    'noflashcheckout',
                    'recurring',
                    's2s',
                    'invoice',
                    'nozeropricing',
                    'reverse',
                ]
            ]
        ]
    ],

    'testAddInvalidFeatureToMerchant' => [
        'request'   => [
            'content' => [
                'names'       => ['invalid'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ],
            'url'     => '/features',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddDuplicateFeatureToMerchant' => [
        'request'   => [
            'content' => [
                'names'       => ['dummy'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ],
            'url'     => '/features',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_ALREADY_ASSIGNED,
        ],
    ],

    'testDeleteNonExistentFeatureFromMerchant' => [
        'request'   => [
            'url'    => '/features/10000000000000/xxxxx',
            'method' => 'delete',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ]
    ],

    'testMultiAssignFeature' => [
        'request'  => [
            'content' => [
                'name'        => 'dummy',
                'entity_ids'  => ['10000000000001', '10000000000002', '10000000000003'],
                'entity_type' => 'merchant'
            ],
            'url'     => '/features/assign',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'                => 'true',
                'HTTP_X-Dashboard-Admin-Username' => 'admin',
                'HTTP_X-Dashboard-User-Email'     => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                [
                    'name'        => 'dummy',
                    'entity_id'   => '10000000000001',
                    'entity_type' => 'merchant'
                ],
                [
                    'name'        => 'dummy',
                    'entity_id'   => '10000000000002',
                    'entity_type' => 'merchant'
                ],
                [
                    'name'        => 'dummy',
                    'entity_id'   => '10000000000003',
                    'entity_type' => 'merchant'
                ],
            ]
        ]
    ],

    'testMultiRemoveFeature' => [
        'request'  => [
            'content' => [
                'name'       => 'dummy',
                'entity_ids' => ['10000000000001', '10000000000002', '10000000000003']
            ],
            'url'     => '/features/remove',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                [
                    'name'        => 'dummy',
                    'entity_id'   => '10000000000001',
                    'entity_type' => 'merchant'
                ],
                [
                    'name'        => 'dummy',
                    'entity_id'   => '10000000000002',
                    'entity_type' => 'merchant'
                ],
                [
                    'name'        => 'dummy',
                    'entity_id'   => '10000000000003',
                    'entity_type' => 'merchant'
                ],
            ]
        ]
    ],

    'testDummyFeatureRouteWithAccess' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/dummy',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testDummyFeatureRouteWithoutAccess' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/dummy',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testGetOnboardingQuestions'  => [
        'request'  => [
            'content' => [
                Constants::FEATURES => [
                    Constants::MARKETPLACE,
                    Constants::SUBSCRIPTIONS,
                    Constants::VIRTUAL_ACCOUNTS
                ]
            ],
            'url'     => '/feature/onboarding',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                Constants::MARKETPLACE      => [
                    Constants::USE_CASE         => [
                        Constants::RESPONSE_TYPE => 'textarea',
                        Constants::MANDATORY     => true
                    ],
                    Constants::SETTLING_TO      => [
                        Constants::RESPONSE_TYPE => 'radio',
                        Constants::MANDATORY     => true
                    ],
                    Constants::VENDOR_AGREEMENT => [
                        Constants::RESPONSE_TYPE => 'file',
                        Constants::MANDATORY     => false
                    ]
                ],
                Constants::SUBSCRIPTIONS    => [
                    Constants::BUSINESS_MODEL  => [
                        Constants::RESPONSE_TYPE => 'textarea',
                        Constants::MANDATORY     => true
                    ],
                    Constants::SAMPLE_PLANS    => [
                        Constants::RESPONSE_TYPE => 'textarea',
                        Constants::MANDATORY     => true
                    ],
                    Constants::WEBSITE_DETAILS => [
                        Constants::RESPONSE_TYPE => 'text',
                        Constants::MANDATORY     => true
                    ]
                ],
                Constants::VIRTUAL_ACCOUNTS => [
                    Constants::USE_CASE                 => [
                        Constants::RESPONSE_TYPE => 'textarea',
                        Constants::MANDATORY     => true
                    ],
                    Constants::EXPECTED_MONTHLY_REVENUE => [
                        Constants::RESPONSE_TYPE => 'number',
                        Constants::MANDATORY     => true
                    ]
                ]
            ],
        ],
    ],

    // Files will be added and verified from the main test function
    'testpostOnboardingResponses' => [
        'request' => [
            'content' => [
                Constants::USE_CASE    => 'Some default use case',
                Constants::SETTLING_TO => 'Someone'
            ],
            'url'     => '/feature/onboarding/marketplace',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

    'getOnboardingResponses'      => [
        'request'  => [
            'content' => [],
            'url'     => '/feature/onboarding/' . Constants::MARKETPLACE . '/responses',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                Constants::USE_CASE    => 'Some default use case',
                Constants::SETTLING_TO => 'Someone'
            ]
        ]
    ],

    'addNotifyFeatures' => [
        'request'  => [
            'content' => [
                'names'       => ['dummy', 'marketplace'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ],
            'url'     => '/features',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                [
                    'name'        => 'dummy',
                    'entity_id'   => '10000000000000',
                    'entity_type' => 'merchant'
                ],
                [
                    'name'        => 'marketplace',
                    'entity_id'   => '10000000000000',
                    'entity_type' => 'merchant'
                ]
            ],
        ],
    ],

    'testGetFeaturesAsMerchant' => [
        'request' => [
            'url' => '/merchants/10000000000000/features',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => false,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'marketplace',
                        'value'        => false,
                        'display_name' => 'Route'
                    ],
                    [
                        'feature'      => 'subscriptions',
                        'value'        => false,
                        'display_name' => 'Subscriptions'
                    ],
                    [
                        'feature'      => 'virtual_accounts',
                        'value'        => false,
                        'display_name' => 'Smart Collect'
                    ],
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testUpdateMerchantFeatures' => [
        'request' => [
            'content' => [
                'features' => [
                    'noflashcheckout' => '1',
                ],
                'optout_reason' => 'some reason'
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [ ],
            'status_code' => 200
        ]
    ],

    'testUpdateMerchantUnEditableFeatures' => [
        'request' => [
            'content' => [
                'features' => [
                    'dummy' => '1'
                ]
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
        ],
    ],

    'testAddMerchantEditableFeaturesOnTest' => [
        'request' => [
            'content' => [
                'features' => [
                    'marketplace' => '1',
                ]
            ],
            'url' => '/merchants/10000000000000/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => false,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'marketplace',
                        'value'        => true,
                        'display_name' => 'Route'
                    ],
                    [
                        'feature'      => 'subscriptions',
                        'value'        => false,
                        'display_name' => 'Subscriptions'
                    ],
                    [
                        'feature'      => 'virtual_accounts',
                        'value'        => false,
                        'display_name' => 'Smart Collect'
                    ],
                ]
            ],
            'status_code' => 200
        ]
    ],
];
