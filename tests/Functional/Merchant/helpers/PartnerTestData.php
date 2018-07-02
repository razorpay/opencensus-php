<?php

namespace RZP\Tests\Functional\Merchant\Partner;

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
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

    'testAddPartnerAccessMap' => [
        'request'   => [
            'url'     => '/merchants/10000000000011/access_maps',
            'method'  => 'POST',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'merchant_id' => '10000000000011',
                'entity_type' => 'application',
            ],
        ],
    ],

    'testAddAccessMapWithoutPartnerContext' => [
        'request'   => [
            'url'     => '/merchants/10000000000011/access_maps',
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

    'testAddAccessMapToPurePlatform' => [
        'request'   => [
            'url'     => '/merchants/10000000000011/access_maps',
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
            'url'     => '/merchants/10000000000011/access_maps',
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

    'testAddPartnerAccessMapAgain' => [
        'request'   => [
            'url'     => '/merchants/10000000000011/access_maps',
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
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_ALREADY_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_ALREADY_EXISTS,
        ],
    ],

    'testRemovePartnerAccessMap' => [
        'request'   => [
            'url'     => '/merchants/10000000000011/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testRemoveNonExistingPartnerAccessMap' => [
        'request'   => [
            'url'     => '/merchants/10000000000011/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_NOT_FOUND,
        ],
    ],

    'testRemovePartnerAccessMapAgain' => [
        'request'   => [
            'url'     => '/merchants/10000000000011/access_maps',
            'method'  => 'DELETE',
            'server' => [
                'HTTP_X-Razorpay-Account' => 'acc_10000000000000',
            ],
            'content' => [],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PARTNER_APP_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_APP_NOT_FOUND,
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

    'testCreateBatchOfPartnerReferralsType' => [
        'request'  => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'partner_referrals',
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'partner_referrals',
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

    'testCreateBatchOfPartnerReferralsTypeFileRows' => [
        [
            Header::PARTNER_MERCHANT_ID  => '10000000000000',
            Header::PARTNER_TYPE         => 'reseller',
            Header::REFERRAL_MERCHANT_ID => '100DemoAccount',
        ],
        [
            Header::PARTNER_MERCHANT_ID  => '10000000000000',
            Header::PARTNER_TYPE         => '',
            Header::REFERRAL_MERCHANT_ID => '10000000000001',
        ],
    ],
];

