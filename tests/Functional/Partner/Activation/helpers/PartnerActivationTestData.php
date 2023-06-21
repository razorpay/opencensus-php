<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Tests\Functional\Fixtures\Entity\Org;

// Note: relative timestamps for use in createDummyMerchantsForWeeklyActivationSummary
$withinTwoMonths  = Carbon::now()->subDays(60)->addHour()->timestamp;
$withinSevenDays  = Carbon::now()->subDays(7)->addHour()->timestamp;
$outsideTwoMonths = Carbon::now()->subDays(60)->subHour()->timestamp;
$outsideSevenDays = Carbon::now()->subDays(7)->subHour()->timestamp;

return [
    'testFetchPartnerActivationForNonRegisteredBusiness' => [
        'request'  => [
            'url'    => '/partner/activation',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'       => '1cXSLlUU8V9sXl',
                    'activation_status' => 'activated',
                    'hold_funds'        => false,
                    'verification'      => [
                        'activation_progress' => 100,
                        'status'              => 'pending'
                    ],
                    'can_submit'        => true
                ],
                'lock_common_fields' => [
                    'contact_name',
                    'contact_mobile',
                    'contact_email',
                    'business_type',
                    'bank_account_name',
                    'bank_account_number',
                    'bank_branch_ifsc',
                    'promoter_pan',
                    'promoter_pan_name',
                    'gstin'
                ],
            ],
        ],
    ],

    'testFetchPartnerActivationFromEs' => [
        'request' => [
            'url'     => '/admins/partner/activation',
            'method'  => 'GET',
            'content' => [
                'activation_status' => 'activated',
            ],
        ],
        'response' => [
            'content' => [
                'items' => [
                    [
                        'merchant_id'       => '1cXSLlUU8V9sXl',
                        'activation_status' => 'activated',
                        'reviewer_id'       => null,
                    ]
                ],
            ],
        ],
    ],

    'testSavePartnerActivationForNonRegisteredBusiness' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'promoter_pan'      => 'EBPPK8222K',
                'promoter_pan_name' => 'User 1',
                'business_operation_address'    => '507, Koramangala 1st block',
                'business_operation_pin'        => '560034',
                'business_operation_city'       => 'Bengaluru',
                'business_operation_state'      => 'KA'
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'  => '1cXSLlUU8V9sXl',
                    'hold_funds'   => false,
                    'verification' => [
                        'activation_progress' => 78,
                        'status'              => 'disabled',
                        'required_fields'     => ['bank_account_number', 'bank_branch_ifsc'],
                    ],
                    'can_submit'   => false
                ]
            ],
        ],
    ],

    'testSavePartnerActivationForRegisteredBusiness' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'bank_account_name'              => 'User 1',
                'bank_account_number'            => '051610000039259',
                'bank_branch_ifsc'               => 'UBIN0805165',
                'business_registered_address'    => '507, Koramangala 1st block',
                'business_registered_pin'        => '560034',
                'business_registered_city'       => 'Bengaluru',
                'business_registered_state'      => 'KA'
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'  => '1cXSLlUU8V9sXl',
                    'hold_funds'   => false,
                    'verification' => [
                        'activation_progress' => 89,
                        'status'              => 'disabled',
                        'required_fields'     => ['company_pan'],
                    ],
                    'can_submit'   => false
                ]
            ],
        ],
    ],

    'testSavePartnerActivationForRegisteredBusinessWithoutPOA' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'bank_account_name'              => 'User 1',
                'bank_account_number'            => '051610000039259',
                'bank_branch_ifsc'               => 'UBIN0805165',
                'business_registered_address'    => '507, Koramangala 1st block',
                'business_registered_pin'        => '560034',
                'business_registered_city'       => 'Bengaluru',
                'business_registered_state'      => 'KA'
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'  => '1cXSLlUU8V9sXl',
                    'hold_funds'   => false,
                    'verification' => [
                        'activation_progress' => 89,
                        'status'              => 'disabled',
                        'required_fields'     => ['company_pan'],
                    ],
                    'can_submit'   => false
                ]
            ],
        ],
    ],

    'testSubmitPartnerActivationForNonRegisteredBusinessActivated' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'submit' => '1',
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'       => '1cXSLlUU8V9sXl',
                    'hold_funds'        => false,
                    'submitted'         => true,
                    'activation_status' => 'activated',
                    'verification'      => [
                        'activation_progress' => 100,
                        'status'              => 'pending',
                    ],
                    'can_submit'        => true
                ]
            ],
        ],
    ],

    'testSubmitPartnerActivationForNonRegisteredBusinessUnderReview' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'submit' => '1',
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'       => '1cXSLlUU8V9sXl',
                    'hold_funds'        => false,
                    'submitted'         => true,
                    'activation_status' => 'under_review',
                    'verification'      => [
                        'activation_progress' => 100,
                        'status'              => 'pending',
                    ],
                    'can_submit'        => true
                ]
            ],
        ],
    ],

    'testSubmitPartnerActivationWhenMerchantActivationLocked' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'submit' => '1',
            ]
        ],
        'response'  => [
            'content'     => [
                'partner_activation' => [
                    'merchant_id'       => '1cXSLlUU8V9sXl',
                    'hold_funds'        => false,
                    'submitted'         => true,
                    'activation_status' => 'under_review',
                    'verification'      => [
                        'activation_progress' => 100,
                        'status'              => 'pending',
                    ],
                    'can_submit'        => true
                ]
            ],
        ],
    ],

    'testSavePartnerActivationWhenMerchantActivationLocked' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'bank_account_name'   => 'User 1',
                'bank_account_number' => '051610000039259',
                'bank_branch_ifsc'    => 'UBIN0805165',
                'promoter_pan'        => 'EBPPK8222K',
                'promoter_pan_name'   => 'User 1',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Merchant activation form has been locked for editing by admin.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED,
        ],
    ],

    'testFetchPartnerActivationForNonPartner' => [
        'request'   => [
            'url'    => '/partner/activation',
            'method' => 'GET'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Merchant is not a partner',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'testSavePartnerActivationForNonPartner' => [
        'request'   => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'bank_account_name'   => 'User 1',
                'bank_account_number' => '051610000039259',
                'bank_branch_ifsc'    => 'UBIN0805165',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Merchant is not a partner',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'saveAllPartnerActivationDetails'          => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'bank_account_name'             => 'User 1',
                'bank_account_number'           => '051610000039259',
                'bank_branch_ifsc'              => 'UBIN0805165',
                'promoter_pan'                  => 'EBPPK8222K',
                'promoter_pan_name'             => 'User 1',
                'business_operation_address'    => '507, Koramangala 1st block',
                'business_operation_pin'        => '560034',
                'business_operation_city'       => 'Bengaluru',
                'business_operation_state'      => 'KA'
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'       => '1cXSLlUU8V9sXl',
                    'hold_funds'        => false,
                    'verification'      => [
                        'activation_progress' => 100,
                        'status'              => 'pending',
                    ],
                    'can_submit'        => true
                ]
            ],
        ],
    ],
    'submitActivationDataForUnVerifiedDetails' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'submit' => '1',
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'                      => '1cXSLlUU8V9sXl',
                    'hold_funds'                       => false,
                    'submitted'                        => true,
                    'activation_status'                => 'under_review',
                    'verification'                     => [
                        'activation_progress' => 100,
                        'status'              => 'pending',
                    ],
                    'can_submit'                       => true,
                    'allowed_next_activation_statuses' => ['needs_clarification', 'activated', 'rejected'],
                ]
            ],
        ],
    ],

    'testActivatePartnerFromUnderReview' => [
        'request'  => [
            'url'     => '/partner/activation/{id}/status',
            'method'  => 'PATCH',
            'content' => [
                'activation_status' => 'activated',
            ]
        ],
        'response' => [
            'content' => [
                'entity_id'   => '1cXSLlUU8V9sXl',
                'entity_name' => 'partner_activation',
                'state'       => 'open',
                'maker_type'  => 'admin'
            ],
        ],
    ],
    'testUpdatePartnerActivationToNeedsClarification' => [
        'request'  => [
            'url'     => '/partner/activation/{id}/status',
            'method'  => 'PATCH',
            'content' => [
                'activation_status' => 'needs_clarification',
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id'                      => '1cXSLlUU8V9sXl',
                'hold_funds'                       => false,
                'locked'                           => false,
                'submitted'                        => true,
                'activation_status'                => 'needs_clarification',
                'kyc_clarification_reasons'        => [
                    'clarification_reasons' => [
                        'contact_name' => [
                            [
                                'field_value' => 'testing',
                                'reason_code' => 'provide_poc',
                                'reason_type' => 'predefined'
                            ]
                        ],
                        'promoter_pan' => [
                            [
                                'field_value' => 'testing',
                                'reason_code' => 'provide_poc',
                                'reason_type' => 'predefined'
                            ]
                        ]
                    ]
                ],
                'allowed_next_activation_statuses' => ['under_review']
            ],
        ],
    ],
    'testPartnerNeedsClarification'                   => [
        'request'  => [
            'url'     => '/partner/activation/{id}',
            'method'  => 'PUT',
            'content' => [
                'kyc_clarification_reasons' => [
                    'clarification_reasons' => [
                        'contact_name' => [
                            [
                                'field_value' => 'testing',
                                'reason_code' => 'provide_poc',
                                'reason_type' => 'predefined'
                            ]
                        ],
                        'promoter_pan' => [
                            [
                                'field_value' => 'testing',
                                'reason_code' => 'provide_poc',
                                'reason_type' => 'predefined'
                            ]
                        ]
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'                      => '1cXSLlUU8V9sXl',
                    'hold_funds'                       => false,
                    'submitted'                        => true,
                    'activation_status'                => 'under_review',
                    'kyc_clarification_reasons'        => [
                        'clarification_reasons' => [
                            'contact_name' => [
                                [
                                    'field_value' => 'testing',
                                    'reason_code' => 'provide_poc',
                                    'reason_type' => 'predefined'
                                ]
                            ],
                            'promoter_pan' => [
                                [
                                    'field_value' => 'testing',
                                    'reason_code' => 'provide_poc',
                                    'reason_type' => 'predefined'
                                ]
                            ]
                        ]
                    ],
                    'allowed_next_activation_statuses' => ['needs_clarification', 'activated', 'rejected']
                ]
            ],
        ],
    ],
    'testUpdatePartnerActivationToRejected' => [
        'request'  => [
            'url'     => '/partner/activation/{id}/status',
            'method'  => 'PATCH',
            'content' => [
                "activation_status" => "rejected",
                "rejection_reasons" => [
                    [
                        "reason_code"     => "get_rich_schemes",
                        "reason_category" => "prohibited_business"
                    ],
                    [
                        "reason_code"     => "multiple_verticals_high_risk",
                        "reason_category" => "high_risk_business"
                    ]
                ]
            ]
        ],
        'response' => [
            'content' => [
                'merchant_id'                      => '1cXSLlUU8V9sXl',
                'hold_funds'                       => true,
                'submitted'                        => true,
                'activation_status'                => 'rejected',
                'allowed_next_activation_statuses' => ['under_review']
            ],
        ],
    ],
    'testInvalidExtraFieldsPartnerDetailsFormSave' => [
        'request'  => [
            'url'     => '/partner/activation',
            'method'  => 'POST',
            'content' => [
                'bank_account_name'      => 'User 1',
                'bank_account_number'    => '051610000039259',
                'bank_branch_ifsc'       => 'UBIN0805165',
                'promoter_pan'           => 'EBPPK8222K',
                'promoter_pan_name'      => 'User 1',
                'bank_beneficiary_state' => 'Telangana'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'bank_beneficiary_state is/are not required and should not be sent',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\ExtraFieldsException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_EXTRA_FIELDS_PROVIDED,
        ],
    ],
    'testInvalidStatusChange' => [
        'request'  => [
            'url'     => '/partner/activation/{id}/status',
            'method'  => 'PATCH',
            'content' => [
                'activation_status' => 'under_review'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'Invalid status change',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testHoldCommissionsAction'                 => [
        'request'  => [
            'url'     => '/partner/{id}/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'hold_commissions'
            ]
        ],
        'response' => [
            'content'     => [
                'merchant_id'       => '1cXSLlUU8V9sXl',
                'activation_status' => 'activated',
                'hold_funds'        => true,
            ],
            'status_code' => 200
        ]
    ],
    'testHoldCommissionsActionInvalidAction'    => [
        'request'   => [
            'url'     => '/partner/{id}/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'hold_commissions'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'partner commissions already on hold',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_COMMISSIONS_ALREADY_ON_HOLD,
        ],
    ],
    'testReleaseCommissionsAction'              => [
        'request'  => [
            'url'     => '/partner/{id}/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'release_commissions'
            ]
        ],
        'response' => [
            'content'     => [
                'merchant_id'       => '1cXSLlUU8V9sXl',
                'activation_status' => 'activated',
                'hold_funds'        => false,
            ],
            'status_code' => 200
        ]
    ],
    'testReleaseCommissionsActionInvalidAction' => [
        'request'   => [
            'url'     => '/partner/{id}/action',
            'method'  => 'PUT',
            'content' => [
                'action' => 'release_commissions'
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => 'BAD_REQUEST_ERROR',
                    'description' => 'partner commissions already released',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_COMMISSIONS_ALREADY_RELEASED,
        ],
    ],

    'testBulkAssignReviewer' => [
        'request' => [
            'content' => [
                'reviewer_id' => Org::SUPER_ADMIN_SIGNED,
                'merchants'   => [
                    '10000000000000'
                ],
            ],
            'url'     => '/partner/activation/bulk_assign_reviewer',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'success'     => 1,
                'failed'      => 0,
                'failedItems' => [],
            ],
        ],
    ],

    'createDummyMerchantsForWeeklyActivationSummary' => [
        'merchantsData'       => [
            '10000000000110' => [
                'merchant'        => ['id' => '10000000000110', 'name' => 'name_10000000000110', 'activated' => 1],
                'merchant_detail' => ['merchant_id' => '10000000000110', 'activation_status' => 'instantly_activated'],
            ],
            '10000000000111' => [
                'merchant'        => ['id' => '10000000000111', 'name' => 'name_10000000000111', 'activated' => 0, 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000111', 'activation_status' => 'under_review', 'submitted' => 1, 'submitted_at' => $withinSevenDays,  'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
            ],
            '10000000000120' => [
                'merchant'                       => ['id' => '10000000000120', 'name' => 'name_10000000000120', 'activated' => 0],
                'merchant_detail'                => ['merchant_id'               => '10000000000120', 'activation_status' => 'needs_clarification',
                                                     'kyc_clarification_reasons' => [
                                                         'clarification_reasons' => [
                                                             'field1' => [[
                                                                              'from'        => 'admin',
                                                                              'reason_type' => 'custom',
                                                                              'field_value' => 'adnakdad',
                                                                              'reason_code' => 'Lorem ipsum',
                                                                          ]],
                                                             'field3' => [[
                                                                              'from'        => 'admin',
                                                                              'reason_type' => 'predefined',
                                                                              'field_value' => 'adnakdad',
                                                                              'reason_code' => 'provide_poc',
                                                                          ]],
                                                         ],
                                                         'additional_details'    => [
                                                             'business_description' => [[
                                                                                            'from'        => 'admin',
                                                                                            'reason_type' => 'predefined',
                                                                                            'field_type'  => 'text',
                                                                                            'reason_code' => 'provide_poc',
                                                                                        ]],
                                                         ],
                                                     ]
                ],
                'expected_clarification_reasons' => [
                    'fields' => [
                        'field1'               => [
                            [
                                'reason_code'        => "others",
                                'reason_description' => "Lorem ipsum",
                                'display_name'       => "Field1"
                            ]
                        ],
                        'field3'               => [
                            [
                                'reason_code'        => "provide_poc",
                                'reason_description' => "Please provide a POC that we can reach out to in case of issues associated with your account.",
                                'display_name'       => "Field3"
                            ]
                        ],
                        'business_description' => [
                            [
                                'reason_code'        => "provide_poc",
                                'reason_description' => "Please provide a POC that we can reach out to in case of issues associated with your account.",
                                'display_name'       => "Business Description"
                            ]
                        ]
                    ]
                ]
            ],
            '10000000000920' => [
                'merchant'        => ['id' => '10000000000920', 'name' => 'name_10000000000920', 'activated' => 0,  'created_at' => $outsideSevenDays, 'updated_at' => $outsideSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000920', 'activation_status' => 'under_review', 'submitted_at' => $outsideSevenDays,  'created_at' => $outsideSevenDays, 'updated_at' => $outsideSevenDays],
            ],
            '10000000000130' => [
                'merchant'        => ['id' => '10000000000130', 'name' => 'name_10000000000130', 'activated' => 0, 'created_at' => $withinTwoMonths, 'updated_at' => $withinTwoMonths],
                'merchant_detail' => ['merchant_id' => '10000000000130', 'activation_status' => null, 'created_at' => $withinTwoMonths, 'updated_at' => $withinTwoMonths],
            ],
            '10000000000930' => [
                'merchant'        => ['id' => '10000000000930', 'name' => 'name_10000000000930', 'activated' => 0, 'created_at' => $outsideTwoMonths, 'updated_at' => $outsideTwoMonths],
                'merchant_detail' => ['merchant_id' => '10000000000930', 'activation_status' => null, 'created_at' => $outsideTwoMonths, 'updated_at' => $outsideTwoMonths],
            ],
            '10000000000140' => [
                'merchant'        => ['id' => '10000000000140', 'name' => 'name_10000000000140', 'activated' => 1, 'activated_at' => $withinSevenDays, 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000140', 'activation_status' => 'activated', 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
            ],
            '10000000000141' => [
                'merchant'        => ['id' => '10000000000141', 'name' => 'name_10000000000141', 'activated' => 1, 'activated_at' => $withinSevenDays, 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000141', 'activation_status' => 'activated_mcc_pending', 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
            ],
            '10000000000142' => [
                'merchant'        => ['id' => '10000000000142', 'name' => 'name_10000000000142', 'activated' => 1, 'activated_at' => $withinSevenDays, 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000142', 'activation_status' => 'activated_kyc_pending', 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays ],
            ],
            '10000000000942' => [
                'merchant'        => ['id' => '10000000000942', 'name' => 'name_10000000000942', 'activated' => 1, 'activated_at' => $outsideSevenDays, 'created_at' => $outsideSevenDays, 'updated_at' => $outsideSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000942', 'activation_status' => 'activated_kyc_pending', 'created_at' => $outsideSevenDays, 'updated_at' => $outsideSevenDays],
            ],
            '10000000000143' => [
                'merchant'        => ['id' => '10000000000143', 'name' => 'name_10000000000143', 'activated' => 0, 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000143', 'activation_status' => 'rejected', 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
                'action_state'    => ['entity_id' => '10000000000143', 'entity_type' => 'workflow_action', 'name' => 'rejected', 'created_at' => $withinSevenDays, 'updated_at' => $withinSevenDays],
            ],
            '10000000000943' => [
                'merchant'        => ['id' => '10000000000943', 'name' => 'name_10000000000943', 'activated' => 0, 'created_at' => $outsideSevenDays, 'updated_at' => $outsideSevenDays],
                'merchant_detail' => ['merchant_id' => '10000000000943', 'activation_status' => 'rejected', 'created_at' => $outsideSevenDays, 'updated_at' => $outsideSevenDays],
                'action_state'    => ['entity_id' => '10000000000943', 'entity_type' => 'workflow_action', 'name' => 'rejected', 'created_at' => $outsideSevenDays, 'updated_at' => $outsideSevenDays],
            ]
        ],
        'expectedFilteredIds' => [
            '10000000000110',
            '10000000000111',
            '10000000000120',
            '10000000000140',
            '10000000000141',
            '10000000000142',
            '10000000000143'
        ]
    ]
];
