<?php

use RZP\Error\ErrorCode;

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
                ]
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
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'  => '1cXSLlUU8V9sXl',
                    'hold_funds'   => false,
                    'verification' => [
                        'activation_progress' => 40,
                        'status'              => 'disabled',
                        'required_fields'     => ['bank_account_name', 'bank_account_number', 'bank_branch_ifsc'],
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
                'bank_account_name'   => 'User 1',
                'bank_account_number' => '051610000039259',
                'bank_branch_ifsc'    => 'UBIN0805165',
            ]
        ],
        'response' => [
            'content' => [
                'partner_activation' => [
                    'merchant_id'  => '1cXSLlUU8V9sXl',
                    'hold_funds'   => false,
                    'verification' => [
                        'activation_progress' => 80,
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
                'bank_account_name'   => 'User 1',
                'bank_account_number' => '051610000039259',
                'bank_branch_ifsc'    => 'UBIN0805165',
                'promoter_pan'        => 'EBPPK8222K',
                'promoter_pan_name'   => 'User 1',
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
                        "reason_code"     => "not_registered_in_india",
                        "reason_category" => "unsupported_business_model"
                    ],
                    [
                        "reason_code"     => "fake_products_or_unlicensed_distribution",
                        "reason_category" => "risky_business"
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
    ]
];
