<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Constants;
use RZP\Error\PublicErrorDescription;

return [

    'testRequestKycAccessByPartner' => [
        'request'  => [
            'url'     => '/partner/kyc_access_request',
            'method'  => 'POST',
            'content' => [
                'entity_id' => '10000000000009',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id' => '10000000000009',
                'entity_type' => 'merchant',
                'partner_id' => '10000000000000',
                'state' => 'pending_approval',
                'rejection_count' => 0,
            ],
        ],
    ],
    'testRequestKycAccessByPartnerAgain' => [
        'request'  => [
            'url'     => '/partner/kyc_access_request',
            'method'  => 'POST',
            'content' => [
                'entity_id' => '10000000000009',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Request failed as kyc access already approved',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_KYC_ACCESS_ALREADY_APPROVED,
        ],
    ],
    'testRequestKycAccessAfterMaxTimesRejected' => [
        'request'  => [
            'url'     => '/partner/kyc_access_request',
            'method'  => 'POST',
            'content' => [
                'entity_id' => '10000000000009',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Request failed as kyc access already rejected',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_KYC_ACCESS_ALREADY_REJECTED,
        ],
    ],
    'testPartnerSubmerchantFetchForKycAccess' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_10000000000009',
                'entity'           => 'merchant',
                'email'            => 'testing@example.com',
                'dashboard_access' => false,
                'kyc_access'       => [
                    'entity_id' => '10000000000009',
                    'entity_type' => 'merchant',
                    'partner_id' => '10000000000000',
                    'state' => 'pending_approval',
                    'rejection_count' => 0,
                ],
            ],
        ],
    ],
    'testRevokeKycAccess' => [
        'request'  => [
            'url'     => '/partner/kyc_revoke_access',
            'method'  => 'POST',
            'content' => [
                'partner_id' => '10000000000000',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id' => '10000000000009',
                'entity_type' => 'application',
                'entity_owner_id' => '10000000000000',
                'has_kyc_access' => false,
            ],
        ],
    ],

    'testConfirmKycAccessRequest' => [
        'request'  => [
            'url'     => '/partner/kyc_approve_reject',
            'method'  => 'POST',
            'content' => [
                'entity_id' => '10000000000009',
                'partner_id' => '10000000000000',
                'approve_token' => 'approve_token',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id' => '10000000000009',
                'entity_type' => 'merchant',
                'partner_id' => '10000000000000',
                'state' => 'approved',
                'rejection_count' => 0,
            ],
        ],
    ],
    'testConfirmKycAccessRequestAgain' => [
        'request'  => [
            'url'     => '/partner/kyc_approve_reject',
            'method'  => 'POST',
            'content' => [
                'entity_id' => '10000000000009',
                'partner_id' => '10000000000000',
                'approve_token' => 'approve_token',
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_RECORDS_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testRejectKycAccessRequest' => [
        'request'  => [
            'url'     => '/partner/kyc_approve_reject',
            'method'  => 'POST',
            'content' => [
                'entity_id' => '10000000000009',
                'partner_id' => '10000000000000',
                'reject_token' => 'reject_token',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id' => '10000000000009',
                'entity_type' => 'merchant',
                'partner_id' => '10000000000000',
                'state' => 'rejected',
                'rejection_count' => 1,
            ],
        ],
    ],
    'testConfirmAfterRejectKycAccessRequest' => [
        'request'  => [
            'url'     => '/partner/kyc_approve_reject',
            'method'  => 'POST',
            'content' => [
                'entity_id' => '10000000000009',
                'partner_id' => '10000000000000',
                'approve_token' => 'approve_token',
            ],
        ],
        'response' => [
            'content' => [
                'entity_id' => '10000000000009',
                'entity_type' => 'merchant',
                'partner_id' => '10000000000000',
                'state' => 'approved',
                'rejection_count' => 1,
            ],
        ],
    ],

    'testSubmerchantKYCByPartnerWithMissingFeatureFlag' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'    => [
                'HTTP_X-Razorpay-Account'    => '10000000000009',
            ],
            'content' => [
                'bank_account_name'    => 'Test',
                'bank_account_number'  => '111000',
                'bank_branch_ifsc'     => 'SBIN0007105',
                'bank_account_type'    => 'savings',
                'business_name'        => 'Test',
                'business_type'        => 1,
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
            ],
        ],
        'response' => [
            'content' => [
                'bank_account_name'    => 'Test',
                'bank_account_number'  => '111000',
                'bank_branch_ifsc'     => 'SBIN0007105',
                'bank_account_type'    => 'savings',
                'business_name'        => 'Test',
                'business_type'        => '1',
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
            ],
        ],
    ],

    'testSubmerchantKYCByPartner' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'    => [
                'HTTP_X-Razorpay-Account'    => '10000000000009',
            ],
            'content' => [
                'bank_account_name'    => 'Test',
                'bank_account_number'  => '111000',
                'bank_branch_ifsc'     => 'SBIN0007105',
                'bank_account_type'    => 'savings',
                'business_name'        => 'Test',
                'business_type'        => 1,
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
            ],
        ],
        'response' => [
            'content' => [
                'bank_account_name'    => 'Test',
                'bank_account_number'  => '111000',
                'bank_branch_ifsc'     => 'SBIN0007105',
                'bank_account_type'    => 'savings',
                'business_name'        => 'Test',
                'business_type'        => '1',
                'business_category'    => 'financial_services',
                'business_subcategory' => 'accounting',
            ],
        ],
    ],

    'testfetchSubmerchantActivationByPartner' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'GET',
            'server'    => [
                'HTTP_X-Razorpay-Account'    => '10000000000009',
            ],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'id' => '10000000000009',
                    'email' => 'testing@example.com',
                    'name' => 'submerchant'
                ]
            ]
        ],
    ],

    'testDocumentUploadForSubmerchant' => [
        'request'  => [
            'url'     => '/merchant/documents/upload',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000009',
            ],
            'content' => [
                'document_type' => 'promoter_address_url'
            ],
        ],
        'response' => [
            'content' => [
                'documents' => [
                    'promoter_address_url' => [

                    ]
                ],
            ]
        ]
    ],

    'testDeleteRelatedEntitiesOnUnmarkingPartner' => [
        'request'   => [
            'url'     => '/merchant/requests/100000RandomId',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'   => [
            'content' => [
                'status' => 'activated',
            ],
        ],
    ],

    'testAddPartnerAccessMapSubmerchantAccessUnauthorized' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ACCESS_DENIED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAddPartnerAccessMap' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'merchant_id'     => '10000000000009',
                'entity_type'     => 'application',
                'entity_owner_id' => '10000000000000',
            ],
        ],
    ],

    'testPartnerSubmerchantLinkViaBatch' => [
        'request'  => [
            'url'     => '/access_map/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'batch_action'  => 'submerchant_link',
                    'entity'        => 'merchant',
                    'partner_id'    => '10000000000000',
                    'merchant_id'   => '10000000000009',
                    'idempotent_id' => 'random',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'batch_action'  => 'submerchant_link',
                        'entity'        => 'merchant',
                        'partner_id'    => '10000000000000',
                        'merchant_id'   => '10000000000009',
                        'idempotent_id' => 'random',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerSubmerchantDeLinkViaBatch' => [
        'request'  => [
            'url'     => '/access_map/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'batch_action'  => 'submerchant_delink',
                    'entity'        => 'merchant',
                    'partner_id'    => '10000000000000',
                    'merchant_id'   => '10000000000009',
                    'idempotent_id' => 'random',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'batch_action'  => 'submerchant_delink',
                        'entity'        => 'merchant',
                        'partner_id'    => '10000000000000',
                        'merchant_id'   => '10000000000009',
                        'idempotent_id' => 'random',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerSubmerchantTypeUpdateViaBatch' => [
        'request'  => [
            'url'     => '/access_map/bulk',
            'method'  => 'POST',
            'content' => [
                [
                    'batch_action'  => 'submerchant_type_update',
                    'entity'        => 'merchant',
                    'partner_id'    => '10000000000000',
                    'merchant_id'   => '10000000000009',
                    'idempotent_id' => 'random',
                ],
                [
                    'batch_action'  => 'submerchant_type_update',
                    'entity'        => 'merchant',
                    'partner_id'    => '10000000000000',
                    'merchant_id'   => '10000000000019',
                    'idempotent_id' => 'random',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'batch_action'  => 'submerchant_type_update',
                        'entity'        => 'merchant',
                        'partner_id'    => '10000000000000',
                        'merchant_id'   => '10000000000009',
                        'idempotent_id' => 'random',
                    ],
                    [
                        'batch_action'  => 'submerchant_type_update',
                        'entity'        => 'merchant',
                        'partner_id'    => '10000000000000',
                        'merchant_id'   => '10000000000019',
                        'idempotent_id' => 'random',
                        'http_status_code' => 400,
                        'error' =>
                            [
                                'description' => 'Partner and merchant are not linked',
                                'code'        => 'BAD_REQUEST_ERROR'
                            ]
                    ]
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerLinkItselfAsSubmerchant' => [
        'request'   => [
            'url'     => '/merchants/10000000000000/access_maps',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_CANNOT_BE_SUBMERCHANT_TO_ITSELF,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_CANNOT_BE_SUBMERCHANT_TO_ITSELF,
        ],
    ],

    'testAddPartnerAccessMapForDiffOrgSubmerchant' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_ID,
        ],
    ],

    'testAddAccessMapWithoutPartnerContext' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'POST',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_CONTEXT_NOT_SET,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testNoSubmerchantAccountAccessForReseller' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testFetchPartnerSubmerchant' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_10000000000009',
                'entity'           => 'merchant',
                'user'             => [],
                'details'          => [
                    'activation_status' => 'under_review',
                ],
                'dashboard_access' => false,
            ],
        ],
    ],

    'testFetchPartnerSubmerchantPurePlatform' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [
                'application_id' => '10000RandomApp',
            ],
        ],
        'response' => [
            'content' => [
                'id'                     => 'acc_10000000000009',
                'entity'                 => 'merchant',
                'user'                   => [],
                'details'                => [
                    'activation_status' => 'under_review',
                ],
                'dashboard_access'       => false,
                'application'            => [
                    'id'   => '10000RandomApp',
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantPurePlatformNoApps' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [
                'application_id' => '10000RandomApp',
            ],
        ],
        'response'   => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_OAUTH_APP_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_OAUTH_APP_NOT_FOUND,
        ],
    ],

    'testFetchPartnerSubmerchantPurePlatformMissingAppId' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MISSING_APPLICATION_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MISSING_APPLICATION_ID,
        ],
    ],

    'testFetchPartnerSubmerchantPurePlatformInvalidAppId' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [
                'application_id' => 'NotExistentApp',
            ],
        ],
        'response'   => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_APPLICATION_ID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_APPLICATION_ID,
        ],
    ],

    'testFetchPartnerSubmerchantProxyAuth' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'id'               => 'acc_10000000000009',
                'entity'           => 'merchant',
                'user'             => [],
                'dashboard_access' => true,
            ],
        ],
    ],

    'testFetchPartnerSubmerchantProxyAuthSellerApp' => [
        'request'  => [
            'url'     => '/submerchants/acc_10000000000009',
            'method'  => 'GET',
            'content' => [],
        ],
        'response'   => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testFetchPartnerSubmerchants' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'user'             => [],
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'dashboard_access' => false,
                    ],
                    [
                        'id'               => 'acc_10000000000011',
                        'entity'           => 'merchant',
                        'user'             => [],
                        'details'          => [
                            'activation_status' => 'activated',
                        ],
                        'dashboard_access' => false,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsOptimised' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'name'             => 'random_name_1',
                        'email'            => 'subm1@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'user'              => [
                            'email'             => 'subm1@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application'       => [
                            'id'=> '8ckeirnw84ifke'
                        ],
                        'kyc_access'        => null,
                    ],
                    [
                        'id'               => 'acc_10000000000011',
                        'name'             => 'random_name_1',
                        'email'            => 'subm2@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => 'activated',
                        ],
                        'user'              => [
                            'email'             => 'subm2@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application'       => [
                            'id'=> '8ckeirnw84ifke'
                        ],
                        'kyc_access'        => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsOptimisedWithContactMobileFilter' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'contact_mobile'      => '9123456788'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [[
                    'id'               => 'acc_10000000000009',
                    'name'             => 'submerchant',
                    'email'            => 'testing@example.com',
                    'hold_funds'       => false,
                    'details'          => [
                        'activation_status' => 'activated',
                    ],
                    'user'             => [
                        'email'             => 'testing@example.com',
                        'contact_mobile'    => '9123456788',
                    ],
                    'dashboard_access'  => false,
                    'kyc_access'        => null,
                ]],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsOptimisedWithContactNoFilter' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'contact_info'    => '9123456788'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [[
                    'id'               => 'acc_10000000000009',
                    'name'             => 'submerchant',
                    'email'            => 'testing@example.com',
                    'hold_funds'       => false,
                    'details'          => [
                        'activation_status' => 'activated',
                    ],
                    'user'             => [
                        'email'             => 'testing@example.com',
                        'contact_mobile'    => '9123456788',
                    ],
                    'dashboard_access'  => false,
                    'kyc_access'        => null,
                ]],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsOptimisedWithEmailFilter' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'contact_info'    => 'testing@example.com'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [[
                    'id'               => 'acc_10000000000009',
                    'name'             => 'submerchant',
                    'email'            => 'testing@example.com',
                    'hold_funds'       => false,
                    'details'          => [
                        'activation_status' => 'activated',
                    ],
                    'user'             => [
                        'email'             => 'testing@example.com',
                        'contact_mobile'    => '9123456788',
                    ],
                    'dashboard_access'  => false,
                    'kyc_access'        => null,
                ]],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsDeleted' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsPurePlatform' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'application' => [
                            'id' => '8ckeirnw84ifke',
                        ]
                    ],
                    [
                        'application' => [
                            'id' => '10000RandomApp',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsPurePlatformOptimised' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'name'             => 'random_name_1',
                        'email'            => 'subm1@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => null,
                        ],
                        'user'              => [
                            'email'             => 'subm1@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application' => [
                            'id' => '8ckeirnw84ifke',
                        ],
                        'kyc_access'        => null,
                    ],
                    [
                        'id'               => 'acc_10000000000009',
                        'name'             => 'random_name_1',
                        'email'            => 'subm1@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => null,
                        ],
                        'user'              => [
                            'email'             => 'subm1@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application' => [
                            'id' => '10000RandomApp',
                        ],
                        'kyc_access'        => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsFilters' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'name'              => 'random_name_1',
                'email'             => 'subm1@xyz.com',
                'id'                => '10000000000009',
                'activation_status' => 'under_review',
                'merchant_id'       => ['10000000000009'],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'user'             => [],
                        'name'             => 'random_name_1',
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'dashboard_access' => false,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsFiltersOptimised' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'name'              => 'random_name_1',
                'email'             => 'subm1@xyz.com',
                'id'                => '10000000000009',
                'activation_status' => 'under_review',
                'merchant_id'       => ['10000000000009'],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'name'             => 'random_name_1',
                        'email'            => 'subm1@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'user'              => [
                            'email'             => 'subm1@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application'       => [
                            'id'=> '8ckeirnw84ifke'
                        ],
                        'kyc_access'        => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsTypeFilter' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'type' => 'referred'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'entity'           => 'merchant',
                        'user'             => [],
                        'name'             => 'random_name_1',
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'dashboard_access' => false,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsTypeFilterOptimised' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'type' => 'referred'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'name'             => 'random_name_1',
                        'email'            => 'subm1@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'user'              => [
                            'email'             => 'subm1@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application'       => [
                            'id'=> '8ckeirnw84ifkf'
                        ],
                        'kyc_access'        => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsFilterByActivationStatus' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'activation_status' => 'not_submitted'
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'name'             => 'random_name_1',
                        'email'            => 'subm1@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => null,
                        ],
                        'user'              => [
                            'email'             => 'subm1@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application'       => [
                            'id'=> '8ckeirnw84ifkf'
                        ],
                        'kyc_access'        => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsPurePlatformFilters' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'application_id' => '10000RandomApp',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'application' => [
                            'id' => '10000RandomApp',
                        ]
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsPurePlatformFiltersOptimised' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'application_id' => '10000RandomApp',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000009',
                        'name'             => 'random_name_1',
                        'email'            => 'subm1@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => null,
                        ],
                        'user'              => [
                            'email'             => 'subm1@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application' => [
                            'id' => '10000RandomApp',
                        ],
                        'kyc_access'        => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsPaginationFilters' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'skip'  => 1,
                'count' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000011',
                        'user'             => [],
                        'details'          => [
                            'activation_status' => 'activated',
                        ],
                        'dashboard_access' => false,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsPaginationFiltersOptimised' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'skip'  => 1,
                'count' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'               => 'acc_10000000000011',
                        'name'             => 'random_name_1',
                        'email'            => 'subm2@xyz.com',
                        'hold_funds'       => false,
                        'details'          => [
                            'activation_status' => 'activated',
                        ],
                        'user'              => [
                            'email'             => 'subm2@xyz.com',
                            'contact_mobile'    => null,
                        ],
                        'dashboard_access'  => false,
                        'application'       => [
                            'id'=> '8ckeirnw84ifke'
                        ],
                        'kyc_access'        => null,
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsEmptyList' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsEmptyListOptimised' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
        ],
    ],

    'testCreatePartnerSubmerchantWithValidContactMobileForPrimary' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'name'           => 'some_very_long_long_name_longer_than_25',
                'email'          => 'user@example.com',
                'contact_mobile' => '9999999999',
                'product'        => 'primary',
            ],
        ],
        'response' => [
            'content' => [
                'name'              => 'some_very_long_long_name_longer_than_25',
                'business_banking'  => false,
                'user' => [
                    'name'           => 'some_very_long_long_name_longer_than_25',
                    'email'          => 'user@example.com',
                    'contact_mobile' => '+919999999999'
                ]
            ],
        ],
    ],

    'testCreatePartnerSubmerchantWithValidContactMobileForX' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'name'           => 'random_name_1',
                'email'          => 'user@example.com',
                'contact_mobile' => '9999999999',
                'product'        => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'name'              => 'random_name_1',
                'business_banking'  => true,
                'user' => [
                    'name'           => 'random_name_1',
                    'email'          => 'user@example.com',
                    'contact_mobile' => '+919999999999'
                ]
            ],
        ],
    ],

    'testCreatePartnerSubmerchantWithInvalidContactMobile' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'name'           => 'random name',
                'email'          => 'user@example.com',
                'contact_mobile' => '9999999',
                'product'        => 'banking',
            ],
        ],
        'response'      => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Contact number should be at least 8 digits, including country code',
                ],
            ],
            'status_code'       => 400,
        ],
        'exception'     => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_PAYMENT_CONTACT_TOO_SHORT,
        ],
    ],

    'testCreatePartnerSubmerchantWithProduct' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'name'    => 'random name',
                'email'   => 'user@example.com',
                'product' => 'banking'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testAddPartnerAccessMapForLinkedAccountSubmerchant' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_PARTNER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_LINKED_ACCOUNT_CANNOT_BE_PARTNER,
        ],
    ],

    'testGetAffiliatedPartnersForMerchant' => [
        'request'  => [
            'url'    => '/merchants/10000000000009/partners',
            'method' => 'GET',
        ],
        'response' => [
            'content'     => [
                'count' => 2,
                'items' => [
                    [
                        'id'           => '10000000000000',
                        'entity'       => 'merchant',
                        'partner_type' => 'fully_managed',
                    ],
                    [
                        'id'           => '10000000000001',
                        'entity'       => 'merchant',
                        'partner_type' => 'reseller',
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdatePartnerTypeAsResellerUsingProxyAuth'   => [
        'request'   => [
            'url'       => '/merchant/partner_type',
            'method'    => 'PATCH',
            'content'   => [
                'partner_type'      => 'reseller',
            ],
        ],
        'response'  => [
            'content'       => [
                'partner_type'              => 'reseller',
                'has_commission_configs'    => true,
            ],
        ],
    ],

    'testCreateLegalDocsConsentForResellerPartner'   => [
        'request'   => [
            'url'       => '/merchant/partner_type',
            'method'    => 'PATCH',
            'content'   => [
                'partner_type'      => 'reseller',
                'consent'           =>  true,
            ],
        ],
        'response'  => [
            'content'       => [
                'partner_type'              => 'reseller',
                'has_commission_configs'    => true,
            ],
        ],
    ],

    'testPartnerSalesPoc'   => [
        'request'   => [
            'url'       => '/partner/sales_poc',
            'method'    => 'GET',
        ],
        'response'  => [
            'content'       => [
                'items' => [
                'Name'              => 'Test Razorpay',
                'Email'             => 'test.sales@example.com',
                'Phone'             => '9876543210',
                'Title'             => 'Partnerships',
                 ]
            ],
        ],
    ],

    'testEmptyPartnerSalesPoc'   => [
        'request'   => [
            'url'       => '/partner/sales_poc',
            'method'    => 'GET',
        ],
        'response'  => [
            'content'       => [
                'items'  => [],
            ],
        ],
    ],

    'testRequestPartnerMigration'   => [
        'request'   => [
            'url'       => '/partner/request_migration',
            'method'    => 'POST',
            'content'   => [
                'website_url'    =>  'random.com',
                'other_info'     =>  'random description',
                'phone_no'       =>  '9999999999',
                'terms'          =>  [
                    'consent'    =>  true,
                    'url'        =>  'https://razorpay.com/s/terms/partners'
                ]
            ],
        ],
        'response'  => [
            'content'       => [
                'success'  => true,
            ],
        ],
    ],

    'testRequestPartnerMigrationError'   => [
        'request'   => [
            'url'       => '/partner/request_migration',
            'method'    => 'POST',
            'content'   => [
                'website_url'    =>  'random.com',
                'other_info'     =>  'random description',
                'phone_no'       =>  '9999999999',
                'terms'          => [
                    'consent'  => true,
                    'url'      => 'https://randompage.com',
                ],
            ],
        ],
        'response'  => [
            'content'       => [],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\ServerErrorException',
            'internal_error_code' => ErrorCode::SERVER_ERROR_PARTNERSHIPS_FAILURE,
        ],
    ],

    'testRequestPartnerMigrationInputValidation'   => [
        'request'   => [
            'url'       => '/partner/request_migration',
            'method'    => 'POST',
            'content'   => [
                'other_info'     =>  'random description',
                'phone_no'       =>  '9999999999'
            ],
        ],
        'response'  => [
            'content'       => [],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdatePartnerTypeAsAggregatorUsingProxyAuth'   => [
        'request'   => [
            'url'       => '/merchant/partner_type',
            'method'    => 'PATCH',
            'content'   => [
                'partner_type'      => 'aggregator',
            ],
        ],
        'response'  => [
            'content'       => [
                'partner_type'              => 'aggregator',
                'has_commission_configs'    => true,
            ],
        ],
    ],

    'testUpdatePartnerTypeAsPurePlatformUsingProxyAuth'   => [
        'request'   => [
            'url'       => '/merchant/partner_type',
            'method'    => 'PATCH',
            'content'   => [
                'partner_type'      => 'pure_platform',
            ],
        ],
        'response'  => [
            'content'       => [
                'partner_type'              => 'pure_platform',
                'has_commission_configs'    => false,
            ],
        ],
    ],

    'testUpdatePartnerTypeUsingProxyAuthWithInvalidPartnerType' => [
        'request'       => [
            'url'       => '/merchant/partner_type',
            'method'    => 'PATCH',
            'content'   => [
                'partner_type'  => 'fully_managed',
            ],
        ],
        'response'      => [
            'content'   => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                ],
            ],
            'status_code'       => 400,
        ],
        'exception'     => [
            'class'                 => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'executePartnerMigration' => [
        'request'  => [
            'url'     => '/partner/activation/migrate',
            'method'  => 'POST',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'count' => 10
            ]
        ]
    ],

    'testMigrateResellerToPurePlatform' => [
        'request' => [
            'content' => [
                'merchant_id'   => 'DefaultPartner',
            ],
            'url'     => '/partner/migrate_reseller_to_pure_platform',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [ 'triggered' => 'true', 'input' => [ 'merchant_id'   => 'DefaultPartner' ] ]
        ]
    ],

    'testFetchEntitiesForPartnershipService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/internal/partnerships/merchant',
        ],
        'response' => [
            'content' => [[
                'merchant'           => [
                    'partner_type'       => 'reseller',
                    'country'            => 'IN'
                ],
                'merchant_details'   => [
                    'activation_status'  => 'activated',
                    'gstin'              => '29ABCDE1234L1Z1'
                ],
                'partner_activation' => [
                    'activation_status'  => 'activated'
                ],
                'commission_balance' => [
                    'balance_id'         => 'balanceIdTest1'
                ]
            ]],
            'status_code' => 200,
        ],
    ],

    'testFetchPartialEntitiesForPartnershipService' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/internal/partnerships/merchant',
        ],
        'response' => [
            'content' => [
                [
                    'merchant'      => [
                        'partner_type' => 'reseller',
                        'country'      => 'IN'
                    ],
                    'tax_components' => [
                        [
                            'name'     => 'CGST 9%',
                            'rate'     => '90000',
                        ],
                        [
                            'name'     => 'SGST 9%',
                            'rate'     => '90000',
                        ]
                    ]
                ],
                [
                    'merchant'       => [
                        'id'           => 'partnerMerchId',
                        'partner_type' => 'reseller',
                        'country'      => 'IN'
                    ],
                    'tax_components' => [
                        [
                            'name'     => 'IGST 18%',
                            'rate'     => '180000',
                        ]
                    ]
                ]
            ],
            'status_code' => 200,
        ],
    ],
];
