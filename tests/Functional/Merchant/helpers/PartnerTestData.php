<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Models\Merchant\Constants;
use RZP\Error\PublicErrorDescription;

return [

    'testMarkingMerchantAsPartner' => [
        'request'  => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'type'        => 'partner',
                'name'        => 'activation',
                'submissions' => [
                    'partner_type' => 'reseller',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'type'        => 'partner',
                'name'        => 'activation',
                'merchant'    => [
                    'id' => '10000000000000',
                ],
                'states'      => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                    ],
                ],
                'submissions' => [
                    'partner_type' => 'reseller',
                ],
            ],
        ],
    ],

    'testMarkingMerchantAsPartnerAgain' => [
        'request'  => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'type'        => 'partner',
                'name'        => 'activation',
                'submissions' => [
                    'partner_type' => 'aggregator',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'type'        => 'partner',
                'name'        => 'activation',
                'merchant'    => [
                    'id' => '10000000000000',
                ],
                'states'      => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                    ],
                ],
                'submissions' => [
                    'partner_type' => 'aggregator',
                ],
            ],
        ],
    ],

    'testSubmerchantKYCByPartnerWithInvalidSubmerchant' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_INVALID_ACCOUNT_ID,
                ],
            ],
            'status_code' => 401,
        ],
    ],

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

    'testMarkingMerchantAsPartnerMissingType' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'type' => 'partner',
                'name' => 'activation',
                'submissions' => [
                    'random_key' => 'random_value',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMarkingMerchantAsPartnerInvalidType' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'type' => 'partner',
                'name' => 'activation',
                'submissions' => [
                    'partner_type' => 'invalid_partner_type',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_TYPE_INVALID,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMarkingMerchantAsPartnerInvalidNameToType' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                // name = activation should only be valid when type = partner
                'type' => 'product',
                'name' => 'activation',
                'submissions' => [
                    'partner_type' => 'reseller',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_REQUEST_INVALID_NAME,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantMarksSelfAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'type' => 'partner',
                'name' => 'activation',
                'submissions' => [
                    'partner_type' => 'reseller',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantUnmarksSelfAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'type' => 'partner',
                'name' => 'deactivation',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_ACTION_NOT_SUPPORTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testApprovingMarkAsPartnerMerchantRequest' => [
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

    'testApprovingMarkAsPartnerWebsiteMissingMerchantRequest' => [
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

    'testMarkPartnerAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests/100000RandomId',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_ALREADY_PARTNER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_ALREADY_PARTNER,
        ],
    ],

    'testUnmarkNonPartnerMerchantAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests/100000RandomId',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'testMarkAsPartnerWithMissingSubmission' => [
        'request'   => [
            'url'     => '/merchant/requests/100000RandomId',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'   => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_REQUEST_SUBMISSIONS_MISSING,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\LogicException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_REQUEST_SUBMISSIONS_MISSING,
        ],
    ],

    'testApprovingUnmarkAsPartnerMerchantRequest' => [
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

    'testUnmarkingMerchantAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests',
            'method'  => 'POST',
            'content' => [
                'type' => 'partner',
                'name' => 'deactivation',
            ],
        ],
        'response' => [
            'content' => [
                'status'      => 'under_review',
                'name'        => 'deactivation',
                'merchant'    => [
                    'id' => '10000000000000',
                ],
                'states'      => [
                    'entity' => 'collection',
                    'items'  => [
                        [
                            'name' => 'under_review',
                        ],
                    ],
                ],
            ],
        ],
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

    'testPartnerSubmerchantMap' => [
        'request'   => [
            'url'     => '/partner_submerchant_map',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'partner_merchant_id'   => '10000000000000',
                'partner_type'          => 'reseller',
                'submerchant_id'        => '10000000000009',
            ],
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

    'testFetchMerchantProducts' => [
        'request'  => [
            'url'     => '/merchant/merchant_products',
            'method'  => 'GET',
            'content' => [
                'merchant_ids' => [
                    '10000000000009',
                ],
                'product' => '',
                'limit'   => 2
            ],
        ],
        'response' => [
            'content' => [
                '10000000000009' => [
                    'banking',
                    'primary',
                ]
            ],
        ],
    ],

    'testFetchMerchantProductsForBankingProduct' => [
        'request'  => [
            'url'     => '/merchant/merchant_products',
            'method'  => 'GET',
            'content' => [
                'merchant_ids' => [
                    '10000000000009',
                    '10000000000010',
                    '10000000000011',
                ],
                'product' => 'banking',
                'limit'   => 2
            ],
        ],
        'response' => [
            'content' => [
                '10000000000009'
            ],
        ],
    ],

    'testAddAccessMapToPurePlatform' => [
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
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testAddAccessMapToNonPartner' => [
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
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_PARTNER_ACTION,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testAddPartnerAccessMapAgain' => [
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

    'testRemoveAccessMapForReseller' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ]
    ],

    'testRemoveAccessMapForAggregator' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ]
    ],

    'testRemoveNonExistingPartnerAccessMap' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
    ],

    'testRemovePartnerToPartnerAccessMap' => [
        'request'   => [
            'url'     => '/merchants/10000000000000/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
    ],

    'testRemovePartnerAccessMapAgain' => [
        'request'   => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
    ],

    'testApprovingPurePlatformDeactivationRequest' => [
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

    'testApprovingPurePlatformActivationRequest' => [
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

    'testLinkedAccountMarkedAsPartner' => [
        'request'   => [
            'url'     => '/merchant/requests/100000RandomId',
            'method'  => 'PATCH',
            'content' => [
                'status' => 'activated',
            ],
        ],
        'response'   => [
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

    'testPartnerSubmerchantsBatch' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'partner_submerchants',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'partner_submerchants',
                'status'           => 'created',
                'total_count'      => 2,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => null,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testPartnerSubmerchantsBatchInvalidId' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'partner_submerchants',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'partner_submerchants',
                'status'           => 'created',
                'total_count'      => 1,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'amount'           => null,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testPartnerSubmerchantTypeChange' => [
        'request'  => [
            'url'     => '/merchants/10000000000009/access_maps',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [
                'from_app_type' => 'referred',
                'to_app_type'   => 'managed',
            ],
        ],
        'response' => [
            'content' => [
                'merchant_id'     => '10000000000009',
                'entity_type'     => 'application',
                'entity_owner_id' => '10000000000000',
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerSubmerchantsBatchFileRows' => [
        [
            Header::PARTNER_MERCHANT_ID  => '10000000000000',
            Header::PARTNER_TYPE         => 'reseller',
            Header::SUBMERCHANT_ID       => '100DemoAccount',
        ],
        [
            Header::PARTNER_MERCHANT_ID  => '10000000000000',
            Header::PARTNER_TYPE         => '',
            Header::SUBMERCHANT_ID       => '10000000000001',
        ],
    ],

    'testPartnerSubmerchantsBatchInvalidIdFileRows' => [
        [
            Header::PARTNER_MERCHANT_ID  => '1NonExistentId',
            Header::PARTNER_TYPE         => 'reseller',
            Header::SUBMERCHANT_ID       => '100DemoAccount',
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

    'testFetchBankingAccountEntitiesForPartnerSubmerchants' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.bank_lms_banking_service_url'),
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [

                ],
            ],
        ],
    ],

    'testFetchBankingAccountEntitiesForPartnerSubmerchantsWithRole' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.bank_lms_banking_service_url'),
            ],
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [

                ],
            ],
        ],
    ],

    'testFetchBankingAccountEntitiesForPartnerSubmerchantsWithInvalidRole' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/banking_account',
            'method'  => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.bank_lms_banking_service_url'),
            ],
            'content' => [],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
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

    'testSendSubmerchantPasswordResetLinkWhenMerchantIsNotAPartner' => [
        'request'   => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWhenSubMerchantUserDoesNotExist' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWhenSubMerchantUserExistAndPartnerMappingDoesNotExist' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWhenSubMerchantUserDoesNotExistForCapitalProduct' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
            'content' => [
                'product'           => 'capital',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWhenSubMerchantUserAndPartnerMappingExist' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWithPrimaryProduct' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
            'content' => [
                'product'           => 'primary',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWithBankingProduct' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
            'content' => [
                'product'           => 'banking',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWithCapitalProduct' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
            'content' => [
                'product'           => 'capital',
            ],
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200,
        ],
    ],

    'testSendSubmerchantPasswordResetLinkWithInvalidProduct' => [
        'request'  => [
            'url'    => '/submerchants/10000000000009/reset_password',
            'method' => 'POST',
            'content' => [
                'product'           => 'invalidName',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Not a valid product: invalidName',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
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

    'testUpdateActivatedCurlecPartnerTypeAsResellerUsingProxyAuth'   => [
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

    'testUpdateActivatedCurlecPartnerTypeAsAggregatorUsingProxyAuth'   => [
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

    'testUpdateInActiveCurlecPartnerTypeAsResellerUsingProxyAuth'   => [
        'request'   => [
            'url'       => '/merchant/partner_type',
            'method'    => 'PATCH',
            'content'   => [
                'partner_type'      => 'reseller',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The merchant has not been activated. This action can only be taken for activated merchants',
                    'source'        => 'NA',
                    'step'        => 'NA',
                    'reason'        => 'NA',
                    'field'         => 'activated',
                ],
            ],
            'status_code' => 400,
        ],
        'exception'     => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
        ],
    ],

    'testUpdateInActiveCurlecPartnerTypeAsAggregatorUsingProxyAuth'   => [
        'request'   => [
            'url'       => '/merchant/partner_type',
            'method'    => 'PATCH',
            'content'   => [
                'partner_type'      => 'aggregator',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The merchant has not been activated. This action can only be taken for activated merchants',
                    'source'        => 'NA',
                    'step'        => 'NA',
                    'reason'        => 'NA',
                    'field'         => 'activated',
                ],
            ],
            'status_code' => 400,
        ],
        'exception'     => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
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
                'phone_no'       =>  '9999999999'
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

    'testUpdatePartnerTypeAsBankOnboardingPartner' => [
        'request'  => [
            'url'     => '/banking_accounts/rbl/lms/merchant/admin/partner_type',
            'method'  => 'PATCH',
            'content' => [
                'merchant_id'  => '10000000000000',
                'partner_type' => 'bank_ca_onboarding_partner',
            ],
        ],
        'response' => [
            'content' => [
                'partner_type' => 'bank_ca_onboarding_partner',
            ],
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

    'testFetchBankingAccountStatusWithVerifiedPanForRBL' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'banking'
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
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'dashboard_access' => false,
                        'banking_account' => [
                            'ca_status' =>  'Application completion pending',
                        ]
                    ]
                ],
            ],
        ],
    ],

    'testFetchSubmsBasedOnProductUsageStatus' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'banking',
                'is_used' => 1
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'        => 'acc_10000000000012',
                        'entity'    => 'merchant',
                    ]
                ],
            ],
        ],
    ],

    'testFetchSubmsBasedOnProductNotUsed' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'banking',
                'is_used' => 0
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'        => 'acc_10000000000009',
                        'entity'    => 'merchant',
                        'name'      => 'random_name_1',
                        'email'     =>  'subm1@xyz.com',
                    ],
                    [
                        'id'        => 'acc_10000000000011',
                        'entity'    => 'merchant',
                        'name'      => 'random_name_1',
                        'email'     =>  'subm2@xyz.com',
                    ]
                ],
            ],
        ],
    ],

    'testIsUsedPassedWithoutProductInQueryParam' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'is_used' => 1
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The product field is required when is used is present.',
                    'reason'        => 'input_validation_failed',
                    'field'         => 'product',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
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

    'testAggregatorToResellerBulkUpdate' => [
        'request' => [
            'content' => [
                'merchant_ids'   => [
                    '10000000000000'
                ],
                'batch_size' => 1
            ],
            'url'     => '/partner/migrate_aggregator_to_reseller/bulk',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => []
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

    'testFetchPartnerSubmerchantsWithInvalidProduct' => [
        'request'   => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'corporatecard',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    "code"        => "BAD_REQUEST_ERROR",
                    "description" => "The selected product is invalid.",
                    "source"      => "business",
                    "step"        => "payment_initiation",
                    "reason"      => "input_validation_failed",
                    "metadata"    => [],
                    "field"       => "product",
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testFetchPartnerSubmerchantsForCapital' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'capital',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'id'     => 'acc_10000000000010',
                    ],
                    [
                        'id'     => 'acc_10000000000009',
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsForCapitalById' => [
        'request'  => [
            'url'     => '/submerchants/{id}',
            'method'  => 'GET',
            'content' => [
                'product' => 'capital',
            ],
        ],
        'response' => [
            'content' => [
                'id'     => 'acc_10000000000010',
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsForCapitalWhenPartnerNotEligible' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'capital',
            ],
        ],
        'response' => [
            'content' => [
                'count'  => 0,
                'entity' => 'collection',
                'items'  => [],
            ],
        ],
    ],

    'testFetchPartnerBankingSubmerchantsForCapital' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'banking',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'     => 'acc_10000000000010',
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsForCapitalBankingSubmerchantsAlsoPresent' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'product' => 'capital',
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'id'     => 'acc_10000000000009',
                    ],
                ],
            ],
        ],
    ],

    'testFetchPartnerSubmerchantsForCapitalByBankingSubmerchantId' => [
        'request'   => [
            'url'     => '/submerchants/{id}',
            'method'  => 'GET',
            'content' => [
                'product' => 'capital',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'No db records found.',
                    'source'      => 'NA',
                    "step"        => 'NA',
                    'reason'      => 'NA',
                    'metadata'    => []
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_RECORDS_FOUND,
        ],
    ],

    'testFetchCapitalApplicationsForSubmerchants' => [
        'request'  => [
            'url'     => '/submerchants/capital/applications',
            'method'  => 'POST',
            'content' => [
                'product_id'  => 'JsP6pHbeMKn10E',
                'merchant_id' => ['10000000000009', '10000000000010'],
            ],
        ],
        'response' => [
            'content'     => [
                "response" => [
                    "10000000000009" => [
                        "partner_applications" => [
                            [
                                "id"                           => "L6P5IPYtcjt7jp",
                                "stage"                        => "Bureau Submission",
                                "state"                        => "STATE_CREATED",
                                "business_name"                => "Nice Technologies",
                                "account_name"                 => "Nice Technologies",
                                "contact_mobile"               => "+918877665",
                                "email"                        => "nice.new.tech+17@gmail.com",
                                "annual_turnover_min"          => "100000",
                                "annual_turnover_max"          => "2000000",
                                "company_address_line_1"       => "Dimholt Industries Pvt. Ltd, BH11",
                                "company_address_line_2"       => "Bada Mandir, MIDC Phase 5",
                                "company_address_city"         => "Balapur",
                                "company_address_state"        => "MH",
                                "company_address_line_country" => "India",
                                "company_address_pincode"      => "442004",
                                "business_type"                => "PROPRIETORSHIP",
                                "business_vintage"             => "UNKNOWN",
                                "gstin"                        => "37ABCBS1234N1Z1",
                                "promoter_pan"                 => "ABCPS1234N",
                                "created_at"                   => "2023-01-20T10:46:39Z",
                                "updated_at"                   => "2023-01-20T15:14:01Z"
                            ]
                        ]
                    ],
                    "10000000000010" => [
                        "partner_applications" => [
                            [
                                "id"                           => "L6PKlqh6cgCI5W",
                                "stage"                        => "Bureau Submission",
                                "state"                        => "STATE_CREATED",
                                "business_name"                => "Nice Technologies",
                                "account_name"                 => "Nice Technologies",
                                "contact_mobile"               => "+918877665",
                                "email"                        => "nice.new.tech+18@gmail.com",
                                "annual_turnover_min"          => "100000",
                                "annual_turnover_max"          => "2000000",
                                "company_address_line_1"       => "Dimholt Industries Pvt. Ltd, BH11",
                                "company_address_line_2"       => "Bada Mandir, MIDC Phase 5",
                                "company_address_city"         => "Balapur",
                                "company_address_state"        => "MH",
                                "company_address_line_country" => "India",
                                "company_address_pincode"      => "442004",
                                "business_type"                => "PROPRIETORSHIP",
                                "business_vintage"             => "UNKNOWN",
                                "gstin"                        => "37ABCBS1234N1Z1",
                                "promoter_pan"                 => "ABCPS1234N",
                                "created_at"                   => "2023-01-20T11:01:18Z",
                                "updated_at"                   => "2023-01-20T15:14:02Z"
                            ]
                        ]
                    ],
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testFetchCapitalApplicationsForSubmerchantsPartnerNotEligible' => [
        'request'   => [
            'url'     => '/submerchants/capital/applications',
            'method'  => 'POST',
            'content' => [
                'product_id' => 'JsP6pHbeMKn10E',
                'merchant_id' => ['10000000000009', '10000000000010'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'The requested URL was not found on the server.',
                    'source'      => 'NA',
                    "step"        => 'NA',
                    'reason'      => 'NA',
                    'metadata'    => []
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_URL_NOT_FOUND,
        ],
    ],

    'testFetchCapitalApplicationsForSubmerchantsIgnoreBankingSubmerchant' => [
        'request'  => [
            'url'     => '/submerchants/capital/applications',
            'method'  => 'POST',
            'content' => [
                'product_id'  => 'JsP6pHbeMKn10E',
                'merchant_id' => ['10000000000009', '10000000000010'],
            ],
        ],
        'response' => [
            'content'     => [
                "response" => [
                    "10000000000009" => [
                        "partner_applications" => [
                            [
                                "id"                           => "L6P5IPYtcjt7jp",
                                "stage"                        => "Bureau Submission",
                                "state"                        => "STATE_CREATED",
                                "business_name"                => "Nice Technologies",
                                "account_name"                 => "Nice Technologies",
                                "contact_mobile"               => "+918877665",
                                "email"                        => "nice.new.tech+17@gmail.com",
                                "annual_turnover_min"          => "100000",
                                "annual_turnover_max"          => "2000000",
                                "company_address_line_1"       => "Dimholt Industries Pvt. Ltd, BH11",
                                "company_address_line_2"       => "Bada Mandir, MIDC Phase 5",
                                "company_address_city"         => "Balapur",
                                "company_address_state"        => "MH",
                                "company_address_line_country" => "India",
                                "company_address_pincode"      => "442004",
                                "business_type"                => "PROPRIETORSHIP",
                                "business_vintage"             => "UNKNOWN",
                                "gstin"                        => "37ABCBS1234N1Z1",
                                "promoter_pan"                 => "ABCPS1234N",
                                "created_at"                   => "2023-01-20T10:46:39Z",
                                "updated_at"                   => "2023-01-20T15:14:01Z"
                            ]
                        ]
                    ],
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerFeatureCheckBySubmerchantWithFeatureEnabled' => [
        'request'  => [
            'url'     => '/submerchant/partner_feature_check/{featureName}',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'feature_enabled' => true,
                'partner_id' => '10000000000001'
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerFeatureCheckBySubmerchantWithInvalidFeatureName' => [
        'request'  => [
            'url'     => '/submerchant/partner_feature_check/{featureName}',
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid feature',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPartnerFeatureCheckBySubmerchantWithEmptyFeatureName' => [
        'request'  => [
            'url'     => '/submerchant/partner_feature_check/{featureName}',
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Feature name not provided',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPartnerFeatureCheckBySubmerchantWithFeatureDisabled' => [
        'request'  => [
            'url'     => '/submerchant/partner_feature_check/{featureName}',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'feature_enabled' => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerFeatureCheckBySubmerchantWithFeatureEnabledOnOAuthApp' => [
        'request'  => [
            'url'     => '/submerchant/partner_feature_check/{featureName}',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'feature_enabled' => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testPartnerFeatureCheckBySubmerchantWithFeatureDisabledOnOAuthApp' => [
        'request'  => [
            'url'     => '/submerchant/partner_feature_check/{featureName}',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'feature_enabled' => false,
            ],
            'status_code' => 200,
        ],
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
