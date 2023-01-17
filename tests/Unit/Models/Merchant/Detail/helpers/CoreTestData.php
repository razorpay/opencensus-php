<?php

return [
    'testGroupBankDetailsInput' => [
        'merchant_id' => '1cXSLlUU8V9sXl',
        'kyc_clarification_reasons' => [
            'clarification_reasons' => [
                'contact_email' => [
                    [
                        'from'        => 'admin',
                        'nc_count'    => 1,
                        'created_at'  => 1673533086,
                        'is_current'  => true,
                        'reason_type' => 'predefined',
                        'field_value' => 'adnakdad',
                        'reason_code' => 'provide_poc',
                    ]
                ],
            ],
            'additional_details'    => [
                'cancelled_cheque'     => [
                    [
                        'from'        => 'system',
                        'nc_count'    => 1,
                        'created_at'  => 1673533086,
                        'is_current'  => true,
                        'reason_type' => 'custom',
                        'field_type'  => 'document',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    ]
                ],
                'bank_account_number' =>[
                    [
                        'from'        => 'system',
                        'nc_count'    => 1,
                        'created_at'  => 1673533086,
                        'is_current'  => true,
                        'reason_type' => 'custom',
                        'field_type'  => 'text',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                     ]
                ],
                'bank_account_name'     => [
                    [
                        'from'        => 'system',
                        'nc_count'    => 1,
                        'created_at'  => 1673533086,
                        'is_current'  => true,
                        'reason_type' => 'custom',
                        'field_type'  => 'document',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    ]
                ],
                'bank_branch_ifsc'     => [
                    [
                        'from'        => 'system',
                        'nc_count'    => 1,
                        'created_at'  => 1673533086,
                        'is_current'  => true,
                        'reason_type' => 'custom',
                        'field_type'  => 'document',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    ]
                ],
                'business_description' => [
                    [
                        'from'        => 'system',
                        'nc_count'    => 1,
                        'created_at'  => 1673533086,
                        'is_current'  => true,
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' => 'provide_poc',
                    ]
                ],
            ],
        ],
    ],
    'testGroupBankDetailsOutput' => [
        'clarification_reasons' => [
            'contact_email' => [
                [
                    'from'        => 'admin',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                ]
            ],
        ],
        'additional_details'    => [
            'bank_account_number' => [
                [
                    'from'        => 'system',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'custom',
                    'field_type'  => 'text',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'cancelled_cheque'     => [
                [
                    'from'        => 'system',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'business_description' => [
                [
                    'from'        => 'system',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]
            ],
            'bank_account_name'     => [
                [
                    'from'        => 'system',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'bank_branch_ifsc'     => [
                [
                    'from'        => 'system',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
        'clarification_reasons_v2' =>[
            'bank_account_number' => [
                [
                    'from'        => 'system',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'custom',
                    'field_type'  => 'text',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    'related_fields'=>[
                        [
                            'field_name' => 'cancelled_cheque'
                        ],
                        [
                            'field_name' => 'bank_account_name'
                        ],
                        [
                            'field_name' => 'bank_branch_ifsc'
                        ]
                    ]
                ]
            ],
            'contact_email' => [
                [
                    'from'        => 'admin',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                ]
            ],
            'business_description' => [
                [
                    'from'        => 'system',
                    'nc_count'    => 1,
                    'created_at'  => 1673533086,
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]
            ],
        ]
    ],
    'testGroupedPromoterPanDetailsInput' => [
        'merchant_id' => '1cXSLlUU8V9sXl',
        'kyc_clarification_reasons' => [
            'clarification_reasons' => [
                'promoter_pan' => [
                    [
                        'from'        => 'admin',
                        'nc_count'    => 1,
                        'created_at'  => 1000,
                        'is_current'  => false,
                        'reason_code' => "invalid_personal_pan_number",
                        'reason_type' => 'predefined',
                    ],
                    [
                        'from'        => 'admin',
                        'nc_count'    => 2,
                        'reason_code' => "invalid_personal_pan_number",
                        'reason_type' => 'predefined',
                        'is_current'  => true,
                        'created_at'  => 2000
                    ]
                ],
                'promoter_pan_name' => [
                    [
                        'from'        => 'admin',
                        'nc_count'    => 2,
                        'reason_code' => "signatory_name_not_matched",
                        'reason_type' => 'predefined',
                        'is_current'  => true,
                        'created_at'  => 2000
                    ]
                ]
            ],
            'additional_details' => [],
        ],
    ],
    'testGroupedPromoterPanDetailsOutput' => [
        'clarification_reasons' => [
            'promoter_pan' => [
                [
                    'from'        => 'admin',
                    'nc_count'    => 1,
                    'reason_type' => 'predefined',
                    'is_current'  => false,
                    'reason_code' => "invalid_personal_pan_number",
                    'created_at'  => 1000
                ],
                [
                    'from'        => 'admin',
                    'nc_count'    => 2,
                    'reason_type' => 'predefined',
                    'is_current'  => true,
                    'reason_code' => "invalid_personal_pan_number",
                    'created_at'  => 2000
                ]
            ],
            'promoter_pan_name' => [
                [
                    'from'        => 'admin',
                    'nc_count'    => 2,
                    'reason_type' => 'predefined',
                    'is_current'  => true,
                    'reason_code' => "signatory_name_not_matched",
                    'created_at'  => 2000
                ]
            ]
        ],
        'additional_details'    => [],
        'clarification_reasons_v2' =>[
            'promoter_pan' => [
                [
                    'from'        => 'admin',
                    'nc_count'    => 1,
                    'reason_code' => "invalid_personal_pan_number",
                    'reason_type' => 'predefined',
                    'is_current'  => false,
                    'created_at'  => 1000,
                    'related_fields'=>[
                        [
                            'field_name' => 'promoter_pan_name'
                        ],
                    ]
                ],
                [
                    'from'        => 'admin',
                    'nc_count'    => 2,
                    'reason_type' => 'predefined',
                    'is_current'  => true,
                    'reason_code' => "invalid_personal_pan_number",
                    'created_at'  => 2000,
                    'related_fields'=>[]
                ]
            ],
            'promoter_pan_name' => [
                [
                    'from'        => 'admin',
                    'nc_count'    => 2,
                    'reason_type' => 'predefined',
                    'is_current'  => true,
                    'created_at'  => 2000,
                    'reason_code' => "signatory_name_not_matched",
                ]
            ]
        ]
    ],
    'testGroupedCompanyPanDetailsInput' => [
        'merchant_id' => '1cXSLlUU8V9sXl',
        'kyc_clarification_reasons' => [
            'clarification_reasons' => [
                'company_pan' => [
                    [
                        'from'        => 'admin',
                        'nc_count'    => 1,
                        'is_current'  => false,
                        'reason_type' => 'predefined',
                        'reason_code' => "invalid_company_pan_number",
                        'created_at'  => 1000
                    ],
                    [
                        'from'        => 'admin',
                        'nc_count'    => 2,
                        'is_current'  => true,
                        'reason_type' => 'predefined',
                        'reason_code' => "invalid_company_pan_number",
                        'created_at'  => 2000
                    ]
                ],
                'company_pan_name' => [
                    [
                        'from'        => 'admin',
                        'is_current'  => true,
                        'reason_type' => 'predefined',
                        'nc_count'    => 2,
                        'reason_code' => "update_director_pan",
                        'created_at'  => 2000
                    ]
                ]
            ],
            'additional_details' => [],
        ],
    ],
    'testGroupedCompanyPanDetailsOutput' => [
        'clarification_reasons' => [
            'company_pan' => [
                [
                    'from'        => 'admin',
                    'is_current'  => false,
                    'reason_type' => 'predefined',
                    'nc_count'    => 1,
                    'reason_code' => "invalid_company_pan_number",
                    'created_at'  => 1000
                ],
                [
                    'from'        => 'admin',
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'nc_count'    => 2,
                    'reason_code' => "invalid_company_pan_number",
                    'created_at'  => 2000
                ]
            ],
            'company_pan_name' => [
                [
                    'from'        => 'admin',
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'nc_count'    => 2,
                    'reason_code' => "update_director_pan",
                    'created_at'  => 2000
                ]
            ]
        ],
        'additional_details'    => [],
        'clarification_reasons_v2' =>[
            'company_pan' => [
                [
                    'from'        => 'admin',
                    'nc_count'    => 1,
                    'is_current'  => false,
                    'reason_type' => 'predefined',
                    'reason_code' => "invalid_company_pan_number",
                    'created_at'  => 1000,
                    'related_fields'=>[
                        [
                            'field_name' => 'company_pan_name'
                        ],
                    ]
                ],
                [
                    'from'        => 'admin',
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'nc_count'    => 2,
                    'reason_code' => "invalid_company_pan_number",
                    'created_at'  => 2000,
                    'related_fields'=>[]
                ]
            ],
            'company_pan_name' => [
                [
                    'from'        => 'admin',
                    'is_current'  => true,
                    'reason_type' => 'predefined',
                    'nc_count'    => 2,
                    'created_at'  => 2000,
                    'reason_code' => "update_director_pan",
                ]
            ]
        ]
    ],
    'existingClarificationReasonV2Data' => [
        'kyc_clarification_reasons' => [
            'clarification_reasons' => [
                'contact_email' => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'admin',
                        'reason_type' => 'predefined',
                        'field_value' => 'adnakdad',
                        'reason_code' => 'provide_poc',
                    ]
                ],
            ],
            'additional_details'    => [
                'bank_account_number' => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'system',
                        'reason_type' => 'custom',
                        'field_type'  => 'text',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    ]
                ],
                'cancelled_cheque'     => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'system',
                        'reason_type' => 'custom',
                        'field_type'  => 'document',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    ]
                ],
                'business_description' => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'system',
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' => 'provide_poc',
                    ]
                ],
                'bank_account_name'     => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'system',
                        'reason_type' => 'custom',
                        'field_type'  => 'document',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    ]
                ],
                'bank_branch_ifsc'     => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'system',
                        'reason_type' => 'custom',
                        'field_type'  => 'document',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    ]
                ],
            ],
            'clarification_reasons_v2' =>[
                'bank_account_number' => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'system',
                        'reason_type' => 'custom',
                        'field_type'  => 'text',
                        'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                        'related_fields'=>[
                            [
                                'field_name' => 'cancelled_cheque'
                            ],
                            [
                                'field_name' => 'bank_account_name'
                            ],
                            [
                                'field_name' => 'bank_branch_ifsc'
                            ]
                        ]
                    ]
                ],
                'contact_email' => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'admin',
                        'reason_type' => 'predefined',
                        'field_value' => 'adnakdad',
                        'reason_code' => 'provide_poc',
                    ]
                ],
                'business_description' => [
                    [
                        'nc_count'    => 1,
                        'from'        => 'system',
                        'reason_type' => 'predefined',
                        'field_type'  => 'text',
                        'reason_code' => 'provide_poc',
                    ]
                ],
            ]]
        ],
    'newClarificationReasonV2Data' => [
        'kyc_clarification_reasons' => [
            'clarification_reasons' => [
                'company_pan' => [
                    [
                        'from'        => 'admin',
                        'reason_code' => "invalid_company_pan_number",
                    ],
                ],
                'company_pan_name' => [
                    [
                        'from'        => 'admin',
                        'reason_code' => "update_director_pan",
                    ]
                ]
            ],
            'additional_details' => [],
        ],
    ],
    'updatedClarificationReasonV2Output' => [
        'nc_count' => 2,
        'clarification_reasons' => [
            'contact_email' => [
                [
                    'nc_count'    => 1,
                    'from'        => 'admin',
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                    'is_current'  => false,
                ]
            ],
            'company_pan' => [
                [
                    'from'        => 'admin',
                    'reason_code' => "invalid_company_pan_number",
                    'is_current'  => true,
                    'created_at'     => 1583548200,
                    'nc_count'    => 2,
                ],
            ],
            'company_pan_name' => [
                [
                    'from'        => 'admin',
                    'reason_code' => "update_director_pan",
                    'is_current'  => true,
                    'created_at'  => 1583548200,
                    'nc_count'    => 2,
                ]
            ]
        ],
        'additional_details'    => [
            'bank_account_number' => [
                [
                    'nc_count'    => 1,
                    'from'        => 'system',
                    'reason_type' => 'custom',
                    'field_type'  => 'text',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    'is_current'  => false,
                ]
            ],
            'cancelled_cheque'     => [
                [
                    'nc_count'    => 1,
                    'from'        => 'system',
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    'is_current'  => false,
                ]
            ],
            'business_description' => [
                [
                    'nc_count'    => 1,
                    'from'        => 'system',
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                    'is_current'  => false,
                ]
            ],
            'bank_account_name'     => [
                [
                    'nc_count'    => 1,
                    'from'        => 'system',
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    'is_current'  => false,
                ]
            ],
            'bank_branch_ifsc'     => [
                [
                    'nc_count'    => 1,
                    'from'        => 'system',
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    'is_current'  => false,
                ]
            ],
        ],
        'clarification_reasons_v2' =>[
            'company_pan' => [
                [
                    'from'           => 'admin',
                    'reason_code'    => "invalid_company_pan_number",
                    'is_current'     => true,
                    'created_at'     => 1583548200,
                    'nc_count'       => 2,
                    'related_fields' => [ ],
                ],
            ],
            'company_pan_name' => [
                [
                    'from'        => 'admin',
                    'reason_code' => "update_director_pan",
                    'is_current'  => true,
                    'created_at'  => 1583548200,
                    'nc_count'    => 2,
                ]
            ],
            'bank_account_number' => [
                [
                    'nc_count'    => 1,
                    'reason_type' => 'custom',
                    'from'        => 'system',
                    'field_type'  => 'text',
                    'is_current'  => false,
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                    'related_fields'=>[
                        [
                            'field_name' => 'cancelled_cheque'
                        ],
                        [
                            'field_name' => 'bank_account_name'
                        ],
                        [
                            'field_name' => 'bank_branch_ifsc'
                        ]
                    ]
                ]
            ],
            'contact_email' => [
                [
                    'nc_count'    => 1,
                    'from'        => 'admin',
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                    'is_current'     => false,
                ]
            ],
            'business_description' => [
                [
                    'nc_count'    => 1,
                    'from'        => 'system',
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                    'is_current'  => false,
                ]
            ],
        ]
    ]
];
