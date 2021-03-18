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
                        'entity'           => 'merchant',
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

    'testFetchPartnerSubmerchantsFilters' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'GET',
            'content' => [
                'name'              => 'random_name_1',
                'email'             => 'user@example.com',
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

    'testCreatePartnerSubmerchantWithProduct' => [
        'request'  => [
            'url'     => '/submerchants',
            'method'  => 'POST',
            'content' => [
                'name'    => 'random_name_1',
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

    'testFetchBankingAccountStatus' => [
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
                        'entity'           => 'merchant',
                        'user'             => [],
                        'details'          => [
                            'activation_status' => 'under_review',
                        ],
                        'dashboard_access' => false,
                        'banking_account' => [
                            'va_status' => null,
                            'ca_status' =>  'created',
                        ]
                    ]
                ],
            ],
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
    ]
];
