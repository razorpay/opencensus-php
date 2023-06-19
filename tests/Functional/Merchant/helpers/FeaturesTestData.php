<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Models\Feature\Constants;
use RZP\Models\NetbankingConfig;
use RZP\Error\PublicErrorDescription;


return [
    'updateFeatureAsMerchant' => [
        'request' => [
            'content' => [
                'features'      => [ ],
            ],
            'url' => '/merchants/me/features',
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

    'testAddBharatQrFeatureToMerchant' => [
        'request'   => [
            'content' => [
                'names'       => ['bharat_qr','bharat_qr_v2'],
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

    'testMswipeFeaturesAdd' => [
        'request'   => [
            'content' => [
                'names'       => ['use_mswipe_terminals'],
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
            'content' => [],
            'status_code' => 200,
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
                'successful' => [
                    'dummy' => [
                        '10000000000001',
                        '10000000000002',
                        '10000000000003'
                    ],
                ],
                'failed'     => [],
            ]
        ]
    ],

    'testMultiAssignFeatures' => [
        'request'  => [
            'content' => [
                'name'        => ['dummy', 'terminal_onboarding'],
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
                'successful' => [
                    'dummy'               => [
                        '10000000000001',
                        '10000000000002',
                        '10000000000003'
                    ],
                    'terminal_onboarding' => [
                        '10000000000001',
                        '10000000000002',
                        '10000000000003'
                    ],
                ],
                'failed'     => [],
            ]
        ]
    ],

    'testMultiAssignBlacklistedFeaturesWhereOneMerchantHasOnlyDS' => [
        'request'  => [
            'content' => [
                'name'        => ['dummy', 'terminal_onboarding','white_labelled_route'],
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ],

    'testMultiRemoveFeature' => [
        'request'  => [
            'content' => [
                'entity_type' => 'merchant',
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
                'successful' => [
                    'dummy' => [
                        '10000000000001',
                        '10000000000002',
                        '10000000000003'
                    ],
                ],
                'failed'     => [],
            ]
        ]
    ],

    'testMultiRemoveFeatureApplicationId' => [
        'request'  => [
            'content' => [
                'entity_type' => 'application',
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
                'successful' => [
                    'dummy' => [
                        '10000000000001',
                        '10000000000002',
                        '10000000000003'
                    ],
                ],
                'failed'     => [],
            ]
        ]
    ],

    'testMultiRemoveFeatureFailure' => [
        'request'  => [
            'content' => [
                'entity_type' => 'merchant',
                'name'       => 'dummy',
                'entity_ids' => ['10000000000001', '10000000000002', '10000000000003']
            ],
            'url'     => '/features/remove',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'successful' => [
                    'dummy' => [
                        '10000000000001',
                        '10000000000003'
                    ],
                ],
                'failed'     => [
                    'dummy' => [
                        '10000000000002'
                    ],
                ],
            ],
        ],
    ],

    'testMultiRemoveFeatureApplicationIdFailure' => [
        'request'  => [
            'content' => [
                'entity_type' => 'application',
                'name'       => 'dummy',
                'entity_ids' => ['10000000000001', '10000000000002', '10000000000003']
            ],
            'url'     => '/features/remove',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'successful' => [
                    'dummy' => [
                        '10000000000001',
                        '10000000000003'
                    ],
                ],
                'failed'     => [
                    'dummy' => [
                        '10000000000002'
                    ],
                ],
            ],
        ],
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
            'content' => [ ],
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
            'url'     => '/onboarding/features',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'questions' => [
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
                ]
            ],
        ],
    ],

    // Files will be added and verified from the main test function
    'postOnboardingResponses' => [
        'request' => [
            'content' => [
                Constants::USE_CASE    => 'Some default use case',
                Constants::SETTLING_TO => 'Someone'
            ],
            'url'     => '/onboarding/features/' . Constants::MARKETPLACE,
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

    'testPostSubscriptionsOnboardingResponses' => [
        'request' => [
            'content' => [
                Constants::BUSINESS_MODEL   => 'Some business model',
                Constants::SAMPLE_PLANS     => 'Some new plans',
                Constants::WEBSITE_DETAILS  => 'http://www.example.com/where_the_link_is_longer_than/50_characters',
            ],
            'url'     => '/onboarding/features/' . Constants::SUBSCRIPTIONS,
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

    'testFileStoreData' => [
        'response' => [
            'content' => [
                'merchant_id'   => '10000000001017',
                'type'          => 'marketplace.vendor_agreement',
                'extension'     => 'pdf',
                'name'          => 'api/10000000001017/marketplace.vendor_agreement',
                'entity'        => 'file_store',
            ]
        ]
    ],

    'getOnboardingResponses'      => [
        'request'  => [
            'content' => [],
            'url'     => '/onboarding/features/' . Constants::MARKETPLACE,
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

    'testGetFeaturesAsMerchant' => [
        'request' => [
            'url' => '/merchants/me/features',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature'      => 'missed_orders_plink',
                        'value'        => false,
                        'display_name' => "Enable missed orders payment-links feature from the dashboard",
                    ],
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => false,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'cred_merchant_consent',
                        'value'        => false,
                        'display_name' => 'Cred Merchant Consent'
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
                    153 => array (
                        'feature' => 'view_opfin_sso_announcement',
                        'value' => true,
                        'display_name' => 'View opfin sso announcemnet',
                    ),
                    154 => array (
                        'feature' => 'view_ssl_banner',
                        'value' => true,
                        'display_name' => 'View SSL banner',
                    ),
                    155 => array (
                        'feature' => 'view_onboarding_cards',
                        'value' => true,
                        'display_name' => 'View onboarding cards',
                    ),
                    192 => [
                        'feature'      => 'payout_service_enabled',
                        'value'        => false,
                        'display_name' => 'Payouts Service',
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
            'url' => '/merchants/me/features',
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

    'testUpdateMerchantFeatureAffordabilityWidget' => [
        'request' => [
            'content' => [
                'features' => [
                    'affordability_widget' => '1',
                ],
                'optout_reason' => 'some reason'
            ],
            'url' => '/merchants/me/features',
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

    'testEnableEsAutomaticFeaturesFailure' => [
        'request' => [
            'content' => [
                'features' => [
                    'es_automatic' => '1'
                ]
            ],
            'url' => '/merchants/me/features',
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

    'testUpdateMerchantProductFeatures' => [
        'request' => [
            'content' => [
                'features' => [
                    'dummy' => '1'
                ]
            ],
            'url' => '/merchants/me/features',
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

    'testUpdateOnboardingResponses' => [
        'request'  => [
            'content' => [],
            'url'     => '/onboarding/features/' . Constants::MARKETPLACE,
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                Constants::USE_CASE    => 'Use case updated',
                Constants::SETTLING_TO => 'Someone else'
            ]
        ]
    ],

    'createMarketplaceOnboardingResponse'  => [
        'request'  => [
            'content' => [
                Constants::USE_CASE    => 'Some default use case',
                Constants::SETTLING_TO => 'Someone'
            ],
            'url'     => '/onboarding/features/' . Constants::MARKETPLACE,
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

    'updateMarketplaceOnboardingResponse'  => [
        'request'  => [
            'content' => [
                Constants::USE_CASE    => 'Use case updated',
                Constants::SETTLING_TO => 'Someone else',
                'merchant_id'          => '10000000001017'
            ],
            'url'     => '/onboarding/features/' . Constants::MARKETPLACE . '/update',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ]
    ],

    'updateMarketplaceOnboardingResponseStatus'  => [
        'request'  => [
            'content' => [ ],
            'url'     => '/onboarding/features/' . Constants::MARKETPLACE . '/status',
            'method'  => 'PUT',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'marketplace_activation_status' => 'rejected'
            ]
        ]
    ],

    'testAddMerchantEditableFeaturesOnTest' => [
        'request' => [
            'content' => [
                'features' => [
                    'marketplace' => '1',
                    'cred_merchant_consent' => '1',
                    'missed_orders_plink' => '1',
                ]
            ],
            'url' => '/merchants/me/features',
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
                        'feature'      => 'missed_orders_plink',
                        'value'        => true,
                        'display_name' => "Enable missed orders payment-links feature from the dashboard",
                    ],
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => false,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'cred_merchant_consent',
                        'value'        => true,
                        'display_name' => 'Cred Merchant Consent'
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

    'testAddMerchantEsAutomaticFeatureOnTest' => [
        'request' => [
            'content' => [
                'features' => [
                    'es_automatic' => '1',
                ]
            ],
            'url' => '/merchants/me/features',
            'method' => 'post',
        ],
        'response' => [
            'content' => [
                'features' => [
                    [
                        'feature'      => 'missed_orders_plink',
                        'value'        => false,
                        'display_name' => "Enable missed orders payment-links feature from the dashboard"
                    ],
                    [
                        'feature'      => 'noflashcheckout',
                        'value'        => false,
                        'display_name' => 'No Flash Checkout'
                    ],
                    [
                        'feature'      => 'cred_merchant_consent',
                        'value'        => false,
                        'display_name' => 'Cred Merchant Consent'
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
                    [
                        'feature'      => 'qr_codes',
                        'value'        => false,
                        'display_name' => 'QR codes'
                    ],
                    [
                        'feature'      => 'bharat_qr',
                        'value'        => false,
                        'display_name' => 'Bharat QR'
                    ],
                    [
                        'feature'      => 'bharat_qr_v2',
                        'value'        => false,
                        'display_name' => 'Bharat QRv2'
                    ],
                    [
                        'feature'      => 'qr_image_content',
                        'value'        => false,
                        'display_name' => 'QR Intent link response'
                    ],
                    [
                        'feature'      => 'qr_image_partner_name',
                        'value'        => false,
                        'display_name' => 'QR codes Partner Name',
                    ],
                    [
                        'feature'      => 'payout',
                        'value'        => false,
                        'display_name' => 'Payouts'
                    ],
                    [
                        'feature'      => 'payouts_batch',
                        'value'        => false,
                        'display_name' => 'Payouts Batch API'
                    ],
                    [
                        'feature'      => 'report_v2',
                        'value'        => false,
                        'display_name' => 'Report V2'
                    ],
                    [
                        'feature'      => 'es_on_demand',
                        'value'        => true,
                        'display_name' => 'On demand Payout'
                    ],
                    [
                        'feature'       => 'es_on_demand_restricted',
                        'value'         => false,
                        'display_name'  => 'Es Ondemand Restricted'
                    ],
                    [
                        'feature'       => 'block_es_on_demand',
                        'value'         => false,
                        'display_name'  => 'Block Es Ondemand',
                    ],
                    [
                        'feature'       => 'updated_imps_ondemand',
                        'value'         => false,
                        'display_name'  => 'Updated IMPS Ondemand',
                    ],
                    [
                        'feature'      => 'es_automatic',
                        'value'        => true,
                        'display_name' => 'Es Automatic'
                    ],
                ]
            ],
            'status_code' => 200
        ]
    ],

    'testAddMerchantRxBlockReportDownloadFeatureAdminAuth' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['rx_block_report_download'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'rx_block_report_download',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testAddMerchantCardTransactionLimit1FeatureAdminAuth'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['cards_transaction_limit_1'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'cards_transaction_limit_1',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddMerchantCardTransactionLimit2FeatureAdminAuth'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['cards_transaction_limit_2'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'cards_transaction_limit_2',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddMerchantDisableOnDemandForLocFeatureInternalAuth'=>[
        'request'  => [
            'url'     => '/internal/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_ondemand_for_loc'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'disable_ondemand_for_loc',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testFailureAddMerchantDisableOnDemandForLocFeatureAdminAuth'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_ondemand_for_loc'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
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
        'testFailureAddMerchantDisableOnDemandForLoanFeatureAdminAuth'=>[
            'request'  => [
                'url'     => '/features',
                'method'  => 'post',
                'content' => [
                    'names'       => ['disable_ondemand_for_loan'],
                    'entity_type' => 'merchant',
                    'entity_id'   => '10000000000000',
                    'should_sync' => true
                ]
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
     'testFailureAddMerchantDisableOnDemandForCardFeatureAdminAuth'=>[
                'request'  => [
                    'url'     => '/features',
                    'method'  => 'post',
                    'content' => [
                        'names'       => ['disable_ondemand_for_card'],
                        'entity_type' => 'merchant',
                        'entity_id'   => '10000000000000',
                        'should_sync' => true
                    ]
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
    'testFailureAddMerchantDisableCardsPostDpdFeatureAdminAuth'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_cards_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
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
    'testFailureAddMerchantDisableLoansPostDpdFeatureAdminAuth'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_loans_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
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
    'testFailureAddMerchantDisableLocPostDpdFeatureAdminAuth'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_loc_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
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
    'testFailAddDisableAmazonISPostDpdAdmin'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_amazonis_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
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
    'testAddMerchantDisableOnDemandForLoanFeatureInternalAuth'=>[
        'request'  => [
            'url'     => '/internal/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_ondemand_for_loan'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'disable_ondemand_for_loan',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddMerchantDisableOnDemandForCardFeatureInternalAuth'=>[
        'request'  => [
            'url'     => '/internal/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_ondemand_for_card'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'disable_ondemand_for_card',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddMerchantDisableLoansPostDpdFeatureInternalAuth'=>[
        'request'  => [
            'url'     => '/internal/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_loans_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'disable_loans_post_dpd',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddMerchantDisableCardsPostDpdFeatureInternalAuth'=>[
        'request'  => [
            'url'     => '/internal/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_cards_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'disable_cards_post_dpd',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddMerchantDisableLocPostDpdFeatureInternalAuth'=>[
        'request'  => [
            'url'     => '/internal/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_loc_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'disable_loc_post_dpd',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddDisableAmazonISPostDpdInternal'=>[
        'request'  => [
            'url'     => '/internal/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['disable_amazonis_post_dpd'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'disable_amazonis_post_dpd',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'testAddMerchantVirtualAccountsFeatureAdminAuthNotify' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['virtual_accounts'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000',
                'should_sync' => true,
            ]
        ],
        'response' => [
            'content' => [
            ]
        ]
    ],

    'verifyProductOnboardingSubmissionStatus' => [
        'request'  => [
            'content' => [
                'status' => 'approved',
                'count'  => 3,
                'skip'   => 0
            ],
            'url'     => '/onboarding/features/submissions/fetch',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                [
                    'merchant_id' => '10000000001017',
                    'product'     => 'marketplace',
                    'status'      => 'approved'
                ]
            ]
        ]
    ],

    'getMarketplaceOnboardingResponseStatus' => [
        'request'  => [
            'content' => [
                'merchant_id' => '10000000001017'
            ],
            'url'     => '/onboarding/features/' . Constants::MARKETPLACE . '/status',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [
                'status' => 'approved'
            ]
        ]
    ],

    'testFetchMerchantFeatures' => [
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
                'assigned_features' => [],
                'all_features' => [
                    'dummy',
                    'webhooks',
                    'aggregator',
                    's2swallet',
                    's2supi',
                    's2saeps',
                    'noflashcheckout',
                    'recurring',
                    's2s',
                    's2s_disable_cards',
                    'invoice',
                    'nozeropricing',
                    'reverse',
                    'broking_report',
                    'dsp_report',
                    'rpp_report',
                    'aggregator_report',
                    'payment_email_fetch',
                    'created_flow',
                    'payout',
                    'payouts_batch',
                    'openwallet',
                    'marketplace',
                    'email_optional',
                    'contact_optional',
                    'subscriptions',
                    'zoho',
                    'expose_downtimes',
                    'payment_failure_email',
                    'virtual_accounts',
                    'qr_image_content',
                    'qr_image_partner_name',
                    'virtual_accounts_banking',
                    'bank_transfer_on_checkout',
                    'checkout_va_with_customer',
                    'invoice_partial_payments',
                    'hide_downtimes',
                    'old_credits_flow',
                    'charge_at_will',
                    'emi_merchant_subvention',
                    'fss_risk_udf',
                    'rule_filter',
                    'tpv',
                    'irctc_report',
                    'disable_maestro',
                    'disable_rupay',
                    'block_intl_recurring',
                    'mobikwik_offers',
                    'skip_hold_funds_on_payout',
                    'report_v2',
                    'corporate_banks',
                    'order_id_mandatory',
                    'order_receipt_unique',
                    'magic',
                    'qr_codes',
                    'new_analytics',
                    'daily_settlement',
                    'disable_upi_intent',
                    'atm_pin_auth',
                    'allow_s2s_apps',
                    'upi_plus',
                    'fss_ipay',
                    'direct_debit',
                    'expose_card_expiry',
                    'expose_card_iin',
                    's2s_optional_data',
                    'void_refunds',
                    'partner',
                    'payment_nobranding',
                    'otpelf',
                    'enable_vpa_validate',
                    'allow_sub_without_email',
                    'hdfc_debit_si',
                    'axis_express_pay',
                    'pre_auth_shield_intg',
                    'bank_transfer_refund',
                    'non_tpv_bt_refund',
                    'card_transfer_refund',
                    'disable_instant_refunds',
                    'log_response',
                    'excess_order_amount',
                    'disable_amount_check',
                    'subscription_v2',
                    'subscription_auth_v2',
                    'expose_arn_refund',
                    'offers',
                    'otp_auth_default',
                    'edit_methods',
                    'capture_queue',
                    'async_capture',
                    'transaction_v2',
                    'es_on_demand',
                    'es_on_demand_restricted',
                    'block_es_on_demand',
                    'updated_imps_ondemand',
                    'es_automatic',
                    'es_automatic_restricted',
                    'ondemand_linked',
                    'ondemand_linked_prepaid',
                    'ondemand_route',
                    'headless_disable',
                    'bepg_disable',
                    'first_data_s2s_flow',
                    'bin_issuer_validator',
                    'offer_private_auth',
                    'qr_custom_txn_name',
                ],
            ],
        ],
    ],
    'testGetMultipleFeaturesInternalAuth' => [
            'request'  => [
                'url'    => '/internal/features/10000000000000',
                'method' => 'get'
            ],
            'response' => [
                'content' => [
                    'assigned_features' => [],
                    'all_features' => [
                        'dummy',
                        'webhooks',
                        'aggregator',
                        's2swallet',
                        's2supi',
                        's2saeps',
                        'noflashcheckout',
                        'recurring',
                        's2s',
                        's2s_disable_cards',
                        'invoice',
                        'nozeropricing',
                        'reverse',
                        'broking_report',
                        'dsp_report',
                        'rpp_report',
                        'aggregator_report',
                        'payment_email_fetch',
                        'created_flow',
                        'payout',
                        'payouts_batch',
                        'openwallet',
                        'marketplace',
                        'email_optional',
                        'contact_optional',
                        'subscriptions',
                        'zoho',
                        'expose_downtimes',
                        'payment_failure_email',
                        'virtual_accounts',
                        'qr_image_content',
                        'qr_image_partner_name',
                        'virtual_accounts_banking',
                        'bank_transfer_on_checkout',
                        'checkout_va_with_customer',
                        'invoice_partial_payments',
                        'hide_downtimes',
                        'old_credits_flow',
                        'charge_at_will',
                        'emi_merchant_subvention',
                        'fss_risk_udf',
                        'rule_filter',
                        'tpv',
                        'irctc_report',
                        'disable_maestro',
                        'disable_rupay',
                        'block_intl_recurring',
                        'mobikwik_offers',
                        'skip_hold_funds_on_payout',
                        'report_v2',
                        'corporate_banks',
                        'order_id_mandatory',
                        'order_receipt_unique',
                        'magic',
                        'qr_codes',
                        'new_analytics',
                        'daily_settlement',
                        'disable_upi_intent',
                        'atm_pin_auth',
                        'allow_s2s_apps',
                        'upi_plus',
                        'fss_ipay',
                        'direct_debit',
                        'expose_card_expiry',
                        'expose_card_iin',
                        's2s_optional_data',
                        'void_refunds',
                        'partner',
                        'payment_nobranding',
                        'otpelf',
                        'enable_vpa_validate',
                        'allow_sub_without_email',
                        'hdfc_debit_si',
                        'axis_express_pay',
                        'pre_auth_shield_intg',
                        'bank_transfer_refund',
                        'non_tpv_bt_refund',
                        'card_transfer_refund',
                        'disable_instant_refunds',
                        'log_response',
                        'excess_order_amount',
                        'disable_amount_check',
                        'subscription_v2',
                        'subscription_auth_v2',
                        'expose_arn_refund',
                        'offers',
                        'otp_auth_default',
                        'edit_methods',
                        'capture_queue',
                        'async_capture',
                        'transaction_v2',
                        'es_on_demand',
                        'es_on_demand_restricted',
                        'block_es_on_demand',
                        'updated_imps_ondemand',
                        'es_automatic',
                        'es_automatic_restricted',
                        'ondemand_linked',
                        'ondemand_linked_prepaid',
                        'ondemand_route',
                        'headless_disable',
                        'bepg_disable',
                        'first_data_s2s_flow',
                        'bin_issuer_validator',
                        'offer_private_auth',
                        'qr_custom_txn_name',
                        'google_pay',
                        'emandate_mrn',
                    ],
                ],
            ],
        ],

    'testFetchMerchantFeaturesCheckBulkApproval' => [
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
                'assigned_features' => [],
                'all_features' => [
                    'dummy',
                    'webhooks',
                    'aggregator',
                    's2swallet',
                    's2supi',
                    's2saeps',
                    'noflashcheckout',
                    'recurring',
                    's2s',
                    's2s_disable_cards',
                    'invoice',
                    'nozeropricing',
                    'reverse',
                    'broking_report',
                    'dsp_report',
                    'rpp_report',
                    'aggregator_report',
                    'payment_email_fetch',
                    'created_flow',
                    'payout',
                    'payouts_batch',
                    'openwallet',
                    'marketplace',
                    'email_optional',
                    'contact_optional',
                    'subscriptions',
                    'zoho',
                    'expose_downtimes',
                    'payment_failure_email',
                    'virtual_accounts',
                    'qr_image_content',
                    'qr_image_partner_name',
                    'virtual_accounts_banking',
                    'bank_transfer_on_checkout',
                    'checkout_va_with_customer',
                    'invoice_partial_payments',
                    'hide_downtimes',
                    'old_credits_flow',
                    'charge_at_will',
                    'emi_merchant_subvention',
                    'fss_risk_udf',
                    'rule_filter',
                    'tpv',
                    'irctc_report',
                    'disable_maestro',
                    'disable_rupay',
                    'block_intl_recurring',
                    'mobikwik_offers',
                    'skip_hold_funds_on_payout',
                    'report_v2',
                    'corporate_banks',
                    'order_id_mandatory',
                    'order_receipt_unique',
                    'magic',
                    'qr_codes',
                    'new_analytics',
                    'daily_settlement',
                    'disable_upi_intent',
                    'atm_pin_auth',
                    'allow_s2s_apps',
                    'upi_plus',
                    'fss_ipay',
                    'direct_debit',
                    'expose_card_expiry',
                    'expose_card_iin',
                    's2s_optional_data',
                    'void_refunds',
                    'partner',
                    'payment_nobranding',
                    'otpelf',
                    'enable_vpa_validate',
                    'allow_sub_without_email',
                    'hdfc_debit_si',
                    'axis_express_pay',
                    'pre_auth_shield_intg',
                    'bank_transfer_refund',
                    'non_tpv_bt_refund',
                    'card_transfer_refund',
                    'disable_instant_refunds',
                    'log_response',
                    'excess_order_amount',
                    'disable_amount_check',
                    'subscription_v2',
                    'subscription_auth_v2',
                    'expose_arn_refund',
                    'offers',
                    'otp_auth_default',
                    'edit_methods',
                    'capture_queue',
                    'async_capture',
                    'transaction_v2',
                    'es_on_demand',
                    'es_on_demand_restricted',
                    'block_es_on_demand',
                    'updated_imps_ondemand',
                    'es_automatic',
                    'es_automatic_restricted',
                    'ondemand_linked',
                    'ondemand_linked_prepaid',
                    'ondemand_route',
                    'headless_disable',
                    'bepg_disable',
                    'first_data_s2s_flow',
                    'bin_issuer_validator',
                    'offer_private_auth',
                    'qr_custom_txn_name',
                ],
            ],
        ],
    ],

    'bulkUpdateFeatureActivationStatus' => [
        'request'  => [
            'content' => [ ],
            'url'     => '/onboarding/features/status/bulk',
            'method'  => 'PUT',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'addFeatures' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
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

    'getDataToAddAccountFeatures' => [
        'request'  => [
            'url'     => '/applications/1000000DemoApp/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['dummy'],
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name'        => 'dummy',
                    'entity_type' => 'application',
                    'entity_id'   => '1000000DemoApp'
                ]
            ]
        ]
    ],

    'verifyFeatureAbsence' => [
        'request' => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'verifyFeaturePresence' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'verifyFeaturePresenceForAccounts' => [
        'request'  => [
            'url'    => '/accounts/100DemoAccount/features',
            'method' => 'get',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'verifyFeaturePresenceForEntity' => [
        'request'  => [
            'url'    => '/features/10000000000000',
            'method' => 'get',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'deleteFeature' => [
        'request'  => [
            'url'     => "/features/10000000000000/dummy",
            'method'  => 'delete',
            'content' => [ ]
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'deleteFeatureInternal' => [
        'request'  => [
            'url'     => "/internal/features/10000000000000/xyz",
            'method'  => 'delete',
            'content' => [ ]
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'deleteFeatureFailure' => [
        'request'  => [
            'url'     => "/internal/features/10000000000000/xyz",
            'method'  => 'delete',
            'content' => [ ]
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


    'getDataToDeleteFeaturesFromEntity' => [
        'request'  => [
            'url'     => "/accounts/100DemoAccount/features/dummy",
            'method'  => 'delete',
            'content' => [ ]
        ],
        'response' => [
            'content' => [ ]
        ]
    ],

    'testAccountLedgerFeaturesDelete' => [
        'request'  => [
            'url'     => "/accounts/10000000000000/features/ledger_reverse_shadow",
            'method'  => 'delete',
            'content' => [
                'should_sync'  => true,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Manually enabling/disabling ledger feature ledger_reverse_shadow is not allowed.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testRestrictedAccessFeatureEnabledAndAccessedByMerchant' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'virtual_account',
                'status' => 'active',
            ],
        ],
    ],

    'testRestrictedAccessFeatureEnabledOnSubmerchantAndAccessedByPartner' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'server'  => [
                'HTTP_X-Razorpay-Account'   => '100submerchant',
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'entity' => 'virtual_account',
                'status' => 'active',
            ],
        ],
    ],

    'testRestrictedAccessFeatureDisabledAndAccessedByMerchant' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/virtual_accounts',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND
                ]
            ],
            'status_code' => 400,
        ],
    ],

    'testAddNonVisibleFeatureToAccount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/accounts/me/features',
            'content' => [
                'names' => ['dummy'],
            ],
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

    'testAddNonEditableFeatureToAccount' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/accounts/me/features',
            'content' => [
                'names' => ['zoho'],
            ],
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

    'testAddProductFeatureToAccountInLive' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/accounts/me/features',
            'content' => [
                'names' => ['marketplace'],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNEDITABLE_IN_LIVE,
        ],
    ],

    'testCheckFeatureStatus' => [
        'request' => [
            'url' => '/feature/merchant/10000000000000/subscriptions',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'status' => true
            ],
            'status_code' => 200
        ]
    ],

    'testCheckFeatureAllProxyAuth' => [
        'request' => [
            'url' => '/feature/merchant/10000000000000/',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'assigned_features' => [
                    ['name' => 'subscriptions']
                ],
            ],
            'status_code' => 200
        ]
    ],

    'testBulkFetchFeatures' => [
        'request' => [
            'url' => '/internal/features/bulk_fetch',
            'method' => 'post',
            'content' => [
                'entity_id'     => '10000000000000',
                'entity_type'   => 'merchant',
                'features' => [
                    'subscriptions',
                    'noflashcheckout',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'features' => [
                    'subscriptions' => true,
                    'noflashcheckout' => false,
                ],
            ],
            'status_code' => 200
        ]
    ],

    'testAddFeatureSkipWorkflowPayoutSpecificAsMerchantTreatmentNotEnabled' => [
        'request' => [
            'content' => [
                'features'      => [
                    'skip_wf_at_payouts' => 1
                ],
            ],
            'url' => '/merchants/me/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You cannot change the value of this feature',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
        ],
    ],

    'testAddFeatureSkipWorkflowPayoutSpecificAsMerchantTreatmentEnabled' => [
        'request' => [
            'content' => [
                'features'      => [
                    'skip_wf_at_payouts' => 1
                ],
            ],
            'url' => '/merchants/me/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You cannot change the value of this feature',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
        ],
    ],

    'testAddRestrictedFeatureSkipWFAtPayouts' => [
        'request' => [
            'content' => [
                'features'      => [
                    'skip_wf_at_payouts' => 1
                ],
            ],
            'url' => '/merchants/me/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You cannot change the value of this feature',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
        ],
    ],

    'testAddRestrictedFeatureSkipWFForPayroll' => [
        'request' => [
            'content' => [
                'features'      => [
                    'skip_wf_for_payroll' => 1
                ],
            ],
            'url' => '/merchants/me/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You cannot change the value of this feature',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
        ],
    ],


    'testAddRestrictedFeatureNewBankingError' => [
        'request' => [
            'content' => [
                'features'      => [
                    'new_banking_error' => 1
                ],
            ],
            'url' => '/merchants/me/features',
            'method' => 'post',
            'server' => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You cannot change the value of this feature',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_UNEDITABLE_FEATURE,
        ],
    ],
    'testAddSkipWorkflowPayoutSpecificFeatureToMerchant' => [
        'request'   => [
            'content' => [
                'names'       => ['skip_wf_at_payouts'],
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
                [
                    "name"      => "skip_wf_at_payouts"
                ],
            ],
        ],
        'status_code' => 200,
    ],

    'testAddMerchantRxShowPayoutSourceFeatureAdminAuth' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['rx_show_payout_source'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'rx_show_payout_source',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testP2pUpiFeature' => [
        'request'   => [
            'content' => [
                'names'       => ['p2p_upi'],
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
                [
                'name' => 'p2p_upi',
                'entity_id' => '10000000000000',
                'entity_type' => 'merchant',
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testDisableTpvFlowFeature' => [
        'request'   => [
            'content' => [
                'names'       => ['disable_tpv_flow'],
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
                [
                    'name' => 'disable_tpv_flow',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testAddCovidFeatureRazorXOff' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['covid_19_relief'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Feature is not live right now',
        ],
    ],

    'testAddCovidFeatureForMerchantWithNoBusinessType' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['covid_19_relief'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Merchant business type is not available',
        ],
    ],

    'testAddCovidFeatureNgoMerchant' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['covid_19_relief'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Cannot Enable covid 19 relief feature, since merchant business type is either NGO or TRUST',
        ],
    ],

    'testAddCovidFeatureForMerchant' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['covid_19_relief'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response'  => [
            'content' => [
                [
                    'name' => 'covid_19_relief',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ],
            'status_code' => 200,
        ]
    ],

    'testAddRTBFeatureMerchantNotActivatedForFourMonths' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['rzp_trusted_badge'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Cannot Enable Trusted Badge feature, since merchant has not been activated for 4 months',
        ],
    ],

    'testAddRTBFeatureMerchantWithDisputes' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['rzp_trusted_badge'],
                'entity_type' => 'merchant',
                'entity_id'   => 'mer12345678900'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Cannot Enable Trusted Badge feature, since open disputes are present',
        ],
    ],

    'testAddVirtualAccountFeatureForUnregisteredMerchant' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['virtual_accounts'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000001'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_VIRTUAL_ACCOUNT_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
            'description'         => 'Virtual account feature cannot be enabled for unregistered merchants.',
        ],
    ],

    'testAddRTBFeatureMerchantLendingCategory' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['rzp_trusted_badge'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Cannot Enable Trusted Badge feature, since merchant category is Lending or DMT',
        ],
    ],

    'testAddRTBFeatureMerchantDifferentOrg' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['rzp_trusted_badge'],
                'entity_type' => 'merchant',
                'entity_id'   => 'mer12345678900'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Cannot Enable Trusted Badge feature, since merchant not part of Razorpay org',
        ],
    ],

    'testAddRTBFeature' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['rzp_trusted_badge'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_FEATURE_UNAVAILABLE,
            'description'         => 'Cannot Enable Trusted Badge feature, since merchant has not been activated for 4 months',
        ],
    ],

    'testAddM2MReferral' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['m2m_referral'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response'  => [
            'content'     => [
                [
                    'name' => 'm2m_referral',
                    'entity_type' => 'merchant',
                    'entity_id'   => '10000000000000'
                ],
            ],
            'status_code' => 200,
        ],
    ],
    'testGetM2MReferralStatusReferralCountNotCrossedLimit' => [
        'request'  => [
            'url'     => '/feature/merchant/10000000000000/m2m_referral',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                    'status' => true,
                ],
            'status_code' => 200,
        ],
    ],
    'testGetNotExistingM2MReferralFeatureStatus' => [
        'request'  => [
            'url'     => '/feature/merchant/10000000000000/m2m_referral',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'status' => false,
            ],
            'status_code' => 200,
        ],
    ],
    'testGetM2MReferralStatusReferralCountCrossedLimit' => [
        'request'  => [
            'url'     => '/feature/merchant/10000000000000/m2m_referral',
            'method'  => 'GET',
        ],
        'response'  => [
            'content'     => [
                'status' => true,
            ],
            'status_code' => 200,
        ],
    ],
    'testMerchantsWithFeatures' => [
        'request'   => [
            'content' => [
                'features'       => ['raas']
            ],
            'url'     => '/internal/feature/merchants',
            'method'  => 'POST',
        ],
        'response'  => [
            'content' => [
                '10000000000000'
            ],
            'status_code' => 200,
        ],
    ],

    'testFeatureStatus' => [
        'request' => [
            'url' => '/merchants/me/features',
            'method' => 'get'
        ],
        'response' => [
            'content' => [
                'features' => []
            ],
            'status_code' => 200
        ]
    ],
    'testAcceptOnly3dsPayments'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['accept_only_3ds_payments'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'accept_only_3ds_payments',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testValidateMCCForBulkPaymentPageFeature'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['file_upload_pp', 'dummy'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000001'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'file_upload_pp',
                    'entity_id' => '10000000000001',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],


    'testValidateMCCForBulkPaymentPageFeatureNegative'=>[
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['file_upload_pp', 'dummy'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000001'
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Financial services are not allowed for bulk payment pages',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],


    'testValidateMCCForBulkPaymentPageFeatureMultiAssign' => [
        'request'  => [
            'content' => [
                'name'       => ['file_upload_pp', 'dummy'],
                'entity_ids'  => ['10000000000001', '10000000000002'],
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
   
            ],
            'status_code' => 200,
        ],
    ],

    'testValidateMCCForBulkPaymentPageFeatureMultiAssignNegative' => [
        'request'  => [
            'content' => [
                'name'       => ['file_upload_pp', 'dummy'],
                'entity_ids'  => ['10000000000001', '10000000000002'],
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
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Financial services are not allowed for bulk payment pages',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOrderReceiptUniqueFeatureFlag' => [
        'request'  => [
            'content' => [
                'name'        => 'order_receipt_unique',
                'entity_ids'  => ['10000000000001'],
                'entity_type' => 'merchant'
            ],
            'url'     => '/internal/features/assign',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'successful' => [
                    'order_receipt_unique' => [
                        '10000000000001'
                    ],
                ],
                'failed'     => [],
            ]
        ]
    ],

    'testOrderReceiptUniqueFeatureFlagFailedInvalidMerchantId' => [
        'request'  => [
            'content' => [
                'name'        => 'order_receipt_unique',
                'entity_ids'  => ['10000000000001'],
                'entity_type' => 'merchant'
            ],
            'url'     => '/internal/features/assign',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'successful' => [],
                'failed'     => [
                    'order_receipt_unique' => [
                        '10000000000001'
                    ],
                ],
            ]
        ]
    ],

    'testMFNFeatureAddition' => [
        'request'   => [
            'content' => [
                'names'       => ['mfn'],
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
                [
                    'name' => 'mfn',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testPayoutServiceFeatureAdditionWhenLedgerReverseShadowIsEnabled' => [
        'request'   => [
            'content' => [
                'names'       => ['payout_service_enabled'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Manually enabling/disabling ledger feature payout_service_enabled is not allowed.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],


    'testFetchBankingConfig' => [
        'request'  => [
            'url'     => '/all_banking_configs',
            'method'  => 'get',
            'content' => []
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],
    ],


    'testUpsertBankingConfig' => [
        'request'  => [
            'url'     => '/banking_configs_upsert',
            'method'  => 'post',
            'content' => [
                'key' => NetbankingConfig\Constants::KEY,
                'field_name' => NetbankingConfig\Constants::AUTO_REFUND_OFFSET,
                'field_value' => 122,
                "short_key" => "netbanking_configurations",
                'entity_id' => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],
    ],

    'testUpsertBankingConfigNegative1' => [
        'request'  => [
            'url'     => '/banking_configs_upsert',
            'method'  => 'post',
            'content' => [
                'key' => "rzp/pg/merchant/emandate/banking_program/NetBankingConfiguration",
                "short_key" => "netbanking_configurations",
                'field_name' => NetbankingConfig\Constants::AUTO_REFUND_OFFSET,
                'field_value' => 122,
                'entity_id' => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            'description' => "key rzp/pg/merchant/emandate/banking_program/NetBankingConfiguration isn't owned by banking"
        ],
    ],


    'testUpsertBankingConfigNegative2' => [
        'request'  => [
            'url'     => '/banking_configs_upsert',
            'method'  => 'post',
            'content' => [
                'key' => NetbankingConfig\Constants::KEY,
                "short_key" => "netbanking_configurations",
                'field_name' => NetbankingConfig\Constants::AUTO_REFUND_OFFSET,
                'field_value' => 122,
                'entity_id' => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNAUTHORIZED,
        ],
    ],


    'testGetBankingConfig' => [
        'request'  => [
            'url'     => '/banking_configs',
            'method'  => 'get',
            'content' => [
                'key' => NetbankingConfig\Constants::KEY,
                "short_key" => "netbanking_configurations",
                'fields' => [
                    NetbankingConfig\Constants::AUTO_REFUND_OFFSET
                ],
                'entity_id' => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                'auto_refund_offset' => 0,
            ],
            'status_code' => 200,
        ],
    ],

    'testLedgerReverseShadowFeatureManualAddition' => [
        'request'   => [
            'content' => [
                'names'       => ['ledger_reverse_shadow'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Manually enabling/disabling ledger feature ledger_reverse_shadow is not allowed.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutServiceFeatureAdditionWhenLedgerJournalReadsIsEnabled' => [
        'request'   => [
            'content' => [
                'names'       => ['payout_service_enabled'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Manually enabling/disabling ledger feature payout_service_enabled is not allowed.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testLedgerJournalReadsFeatureManualAddition' => [
        'request'   => [
            'content' => [
                'names'       => ['ledger_journal_reads'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Manually enabling/disabling ledger feature ledger_journal_reads is not allowed.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testLedgerReverseShadowFeatureAdditionWhenLedgerJournalWritesIsDisabled' => [
        'request'   => [
            'content' => [
                'names'       => ['ledger_reverse_shadow'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Enabling ledger_reverse_shadow is not allowed when ledger_journal_writes is not enabled.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testLedgerJournalReadsFeatureAdditionWhenLedgerJournalWritesIsDisabled' => [
        'request'   => [
            'content' => [
                'names'       => ['ledger_journal_reads'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Enabling ledger_journal_reads is not allowed when ledger_journal_writes is not enabled.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testHighTpsCompositePayoutFeatureAdditionWhenLedgerReverseShadowIsEnabled' => [
        'request'   => [
            'content' => [
                'names'       => ['high_tps_composite_payout'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Enabling high_tps_composite_payout is not allowed when ledger_reverse_shadow is already enabled.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testHighTpsPayoutEgressFeatureAdditionWhenLedgerReverseShadowIsEnabled' => [
        'request'   => [
            'content' => [
                'names'       => ['high_tps_payout_egress'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Enabling high_tps_payout_egress is not allowed when ledger_reverse_shadow is already enabled.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]

    ],

    'testLedgerReverseShadowFeatureManualAdditionFromBulk' => [
        'request'  => [
            'content' => [
                'name'        => 'ledger_reverse_shadow',
                'entity_ids'  => ['10000000000000', '10000000000001'],
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
                'successful' => [],
                'failed'     => [
                    'ledger_reverse_shadow' => [
                        '10000000000000',
                        '10000000000001'
                    ],
                ],
            ]
        ]
    ],

    'testDualCheckoutFeature' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_dual_checkout'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_dual_checkout',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testLedgerReverseShadowFeatureManualRemoveFromBulk' => [
        'request'  => [
            'content' => [
                'entity_type' => 'merchant',
                'name'       => 'ledger_reverse_shadow',
                'entity_ids' => ['10000000000000', '10000000000001']
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
                'successful' => [],
                'failed'     => [
                    'ledger_reverse_shadow' => [
                        '10000000000000',
                        '10000000000001'
                    ],
                ],
            ]
        ]
    ],

    'testOnboardOldAccountsToLedger' => [
        'request'  => [
            'content' => [
                [
                    'idempotency_key' => 'idempotency_key',
                    'merchant_id'     => '10000000000000',
                    'action'          => 'reverse_shadow',
                ]
            ],
            'url'     => '/onboarding/feature/ledger/onboard_old_accounts',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     =>  [
                        [
                            'idempotency_key'   => 'idempotency_key',
                            'merchant_id'       => '10000000000000',
                            'status'            => 'success',
                        ],
                    ],
            ]
        ]
    ],

    'testOnboardOldAccountsToLedgerAndRemoveReverseShadowWhenPSEnabled' => [
        'request'  => [
            'content' => [
                [
                    'idempotency_key' => 'idempotency_key',
                    'merchant_id'     => '10000000000000',
                    'action'          => 'reverse_shadow_offboard',
                ]
            ],
            'url'     => '/onboarding/feature/ledger/onboard_old_accounts',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     =>  [
                    [
                        'idempotency_key'   => 'idempotency_key',
                        'merchant_id'       => '10000000000000',
                        'status'            => 'failed',
                        'error'             => [
                            'description'   => 'ledger_reverse_shadow can not be removed if payout_service_enabled is assigned to the merchant.'
                        ]
                    ],
                ],
            ]
        ]
    ],

    'testOnboardOldAccountsToLedgerAndRemoveReverseShadowWhenPSNotEnabled' => [
        'request'  => [
            'content' => [
                [
                    'idempotency_key' => 'idempotency_key',
                    'merchant_id'     => '10000000000000',
                    'action'          => 'reverse_shadow_offboard',
                ]
            ],
            'url'     => '/onboarding/feature/ledger/onboard_old_accounts',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     =>  [
                    [
                        'idempotency_key'   => 'idempotency_key',
                        'merchant_id'       => '10000000000000',
                        'status'            => 'success',
                    ],
                ],
            ]
        ]
    ],

    'testOnboardMerchantOnPGSuccess' => [
        'request'  => [
            'content' => [
                    'merchant_ids'     => ['10000000000000']
            ],
            'url'     => '/pg_ledger/merchant/onboard',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 1,
                'items'     =>  [
                    [
                        'merchant_id'       => '10000000000000',
                        'status'            => 'success',
                        'feature'           => 'pg_ledger_journal_writes',
                        'message'           => 'merchant onboarded'
                    ],
                ],
            ]
        ]
    ],

    'testOnboardMerchantOnPGFailure' => [
        'request'  => [
            'content' => [
                    'merchant_ids'     => ['10000000000000',"Jz6THQeX9RAYWH"]
            ],
            'url'     => '/pg_ledger/merchant/onboard',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'entity'    => 'collection',
                'count'     => 2,
                'items'     =>  [
                    [
                        'merchant_id'       => '10000000000000',
                        'status'            => 'failure',
                        'feature'           => 'pg_ledger_journal_writes',
                        'message'           => 'merchant feature already enabled'
                    ],
                    [
                        'merchant_id'       => 'Jz6THQeX9RAYWH',
                        'status'            => 'failure',
                        'feature'           => 'pg_ledger_journal_writes',
                        'message'           => 'The id provided does not exist'
                    ],
                ],
            ]
        ]
    ],

    'test1CCReportingTestFeature' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_reporting_test'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_reporting_test',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testAddRecurringCheckoutDotComFeature' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['recurring_chkout_dot_com'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'recurring_chkout_dot_com',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'test1CCOverrideTheme' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_override_theme'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_override_theme',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],
    'test1CCInputEnglish' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_input_english'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_input_english',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testOneCcStoreAccountFeature' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_store_account'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_store_account',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testPayoutServiceIdempotencyPsToApiFeaturesManualAddition' => [
        'request'  => [
            'content' => [
                'names'       => ['idempotency_ps_to_api'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>
                        'Manually enabling/disabling payout service feature idempotency_ps_to_api is not allowed.'

                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutServiceIdempotencyPsToApiFeaturesManualAdditionFromBulk' => [
        'request'  => [
            'content' => [
                'name'        => 'idempotency_ps_to_api',
                'entity_ids'  => ['10000000000000', '10000000000001'],
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
            'content'     => [
                'successful' => [
                ],
                'failed'     => [
                    'idempotency_ps_to_api' => [
                        '10000000000000',
                        '10000000000001',
                    ],
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testPayoutServiceIdempotencyApiToPsFeaturesManualAddition' => [
        'request'  => [
            'content' => [
                'names'       => ['idempotency_api_to_ps'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>
                        'Manually enabling/disabling payout service feature idempotency_api_to_ps is not allowed.'

                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutServiceIdempotencyApiToPsFeaturesManualAdditionFromBulk' => [
        'request'  => [
            'content' => [
                'name'        => 'idempotency_api_to_ps',
                'entity_ids'  => ['10000000000000', '10000000000001'],
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
            'content'     => [
                'successful' => [
                ],
                'failed'     => [
                    'idempotency_api_to_ps' => [
                        '10000000000000',
                        '10000000000001',
                    ],
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testPayoutServiceIdempotencyPsToApiFeaturesManualDelete' => [
        'request'   => [
            'url'     => "/accounts/10000000000000/features/idempotency_ps_to_api",
            'method'  => 'delete',
            'content' => [
                'should_sync' => true,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>
                        'Manually enabling/disabling payout service feature idempotency_ps_to_api is not allowed.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutServiceIdempotencyApiToPsFeaturesManualDelete' => [
        'request'  => [
            'url'     => "/accounts/10000000000000/features/idempotency_api_to_ps",
            'method'  => 'delete',
            'content' => [
                'should_sync' => true,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>
                        'Manually enabling/disabling payout service feature idempotency_api_to_ps is not allowed.'

                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testPayoutServiceIdempotencyPsToApiFeaturesManualDeleteFromBulk' => [
        'request'  => [
            'content' => [
                'entity_type' => 'merchant',
                'name'        => 'idempotency_ps_to_api',
                'entity_ids'  => ['10000000000000', '10000000000001']
            ],
            'url'     => '/features/remove',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content'     => [
                'successful' => [
                ],
                'failed'     => [
                    'idempotency_ps_to_api' => [
                        '10000000000000',
                        '10000000000001',
                    ],
                ],
            ],
            'status_code' => 200
        ]
    ],

    'testPayoutServiceIdempotencyApiToPsFeaturesManualDeleteFromBulk' => [
        'request'  => [
            'content' => [
                'entity_type' => 'merchant',
                'name'        => 'idempotency_api_to_ps',
                'entity_ids'  => ['10000000000000', '10000000000001']
            ],
            'url'     => '/features/remove',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content'     => [
                'successful' => [
                ],
                'failed'     => [
                    'idempotency_api_to_ps' => [
                        '10000000000000',
                        '10000000000001',
                    ],
                ],
            ],
            'status_code' => 200
        ]
    ],

    'testPayoutServiceEnabledFeatureManualAddition' => [
        'request'  => [
            'content' => [
                'names'       => ['payout_service_enabled'],
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
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>
                        'Manually enabling/disabling ledger feature payout_service_enabled is not allowed.'

                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutServiceEnabledFeatureManualAdditionFromBulk' => [
        'request'  => [
            'content' => [
                'name'        => 'payout_service_enabled',
                'entity_ids'  => ['10000000000000', '10000000000001'],
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
            'content'     => [
                'successful' => [
                ],
                'failed'     => [
                    'payout_service_enabled' => [
                        '10000000000000',
                        '10000000000001',
                    ],
                ],
            ],
            'status_code' => 200
        ],
    ],

    'testPayoutServiceEnabledFeatureManualDeleteFromBulk' => [
        'request'  => [
            'content' => [
                'entity_type' => 'merchant',
                'name'        => 'payout_service_enabled',
                'entity_ids'  => ['10000000000000', '10000000000001']
            ],
            'url'     => '/features/remove',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard'            => 'true',
                'HTTP_X-Dashboard-User-Email' => 'user@rzp.dev',
            ],
        ],
        'response' => [
            'content'     => [
                'successful' => [
                ],
                'failed'     => [
                    'payout_service_enabled' => [
                        '10000000000000',
                        '10000000000001',
                    ],
                ],
            ],
            'status_code' => 200
        ]
    ],

    'testPayoutServiceEnabledFeatureManualDelete' => [
        'request'  => [
            'url'     => "/accounts/10000000000000/features/payout_service_enabled",
            'method'  => 'delete',
            'content' => [
                'should_sync' => true,
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>
                        'Manually enabling/disabling ledger feature payout_service_enabled is not allowed.'

                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'test1ccCustomerConsent' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_consent_default'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_consent_default',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'test1ccCustomerConsentNotDefault' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_consent_notdefault'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_consent_notdefault',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testRemovePayoutServiceIntermediateIdempotencyKeyFeatures' => [
        'request'  => [
            'url'     => '/ps_idempotency_key_feature_remove',
            'method'  => 'post',
            'content' => [
            ]
        ],
        'response' => [
            'content'     => [
                'idempotency_api_to_ps' => [
                    'success' => 1,
                    'failure' => 0,
                ],
                'idempotency_ps_to_api' => [
                    'success' => 1,
                    'failure' => 0,
                ],
            ],
            'status_code' => 200
        ],
    ],
    'test1ccDisableEmailCookie' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['one_cc_disableemailcookie'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'one_cc_disableemailcookie',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ],
    ],

    'testAddBlacklistedFeatureWithoutOnlyDS' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['white_labelled_route'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
        ],
        'response' => [
            'content' => [
                [
                    'name' => 'white_labelled_route',
                    'entity_id' => '10000000000000',
                    'entity_type' => 'merchant',
                ]
            ]
        ]
    ],

    'testAddBlackListedFeatureWithOnlyDS' => [
        'request'  => [
            'url'     => '/features',
            'method'  => 'post',
            'content' => [
                'names'       => ['white_labelled_route'],
                'entity_type' => 'merchant',
                'entity_id'   => '10000000000000'
            ]
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_FEATURE_NOT_ALLOWED_FOR_MERCHANT,
        ],
    ]
];
