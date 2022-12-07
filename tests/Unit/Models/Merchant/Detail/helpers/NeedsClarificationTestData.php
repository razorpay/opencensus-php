<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;

return [

    'testClarificationReasonTransformationInput' => [
        'clarification_reasons' => [
            'contact_name'  => [
                [
                    'from'        => 'admin',
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'contact_email' => [
                [
                    'from'        => 'admin',
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                ]
            ],
        ],
        'additional_details'    => [
            'cancelled_cheque'     => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'business_description' => [
                [
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]
            ],
        ],
    ],

    'testClarificationReasonTransformationOutput' => [
        'fields'    => [
            'contact_name'         => [
                [
                    'reason_code'        => 'others',
                    'display_name'       => 'Contact Name',
                    'reason_description' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'contact_email'        => [
                [
                    'reason_code'        => 'provide_poc',
                    'display_name'       => 'Contact Email',
                    'reason_description' => 'Please provide a POC that we can reach out to in case of issues associated with your account.',
                ]
            ],
            'business_description' => [
                [
                    'reason_code'        => 'provide_poc',
                    'display_name'       => 'Business Description',
                    'reason_description' => 'Please provide a POC that we can reach out to in case of issues associated with your account.',
                ]
            ]
        ],
        'documents' => [
            'cancelled_cheque' => [
                [
                    'reason_code'        => 'others',
                    'display_name'       => 'Cancelled Cheque',
                    'reason_description' => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ]
    ],

    'testClarificationReasonMergeInput' => [
        'clarification_reasons' => [
            'business_type' => [
                [
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason_code'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
        'additional_details'    => [
            'business_proof_url' => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
    ],

    'testClarificationReasonMergeOutput' => [
        'clarification_reasons' => [
            'contact_name'  => [
                [
                    'from'        => 'admin',
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason_code'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'contact_email' => [
                [
                    'from'        => 'admin',
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                ]
            ],
            'business_type' => [
                [
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason_code'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
        'additional_details'    => [
            'cancelled_cheque'     => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
            'business_description' => [
                [
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]
            ],
            'business_proof_url'   => [
                [
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]
            ],
        ],
    ],

    'testComposerForNcBvsEntity'        => [
        'artefact_type' => 'gstin',
        'error_code'    => 'RULE_EXECUTION_FAILED',
        'rule_execution_list' => [
            0 => [
                'rule' => [
                    'rule_type' => 'string_comparison_rule',
                    'rule_def' => [
                        'or' => [
                            0 => [
                                'fuzzy_wuzzy' => [
                                    0 => [
                                        'var' => 'artefact.details.legal_name.value',
                                    ],
                                    1 => [
                                        'var' => 'enrichments.online_provider.details.legal_name.value',
                                    ],
                                    2 => 70,
                                ],
                            ],
                            1 => [
                                'fuzzy_wuzzy' => [
                                    0 => [
                                        'var' => 'artefact.details.trade_name.value',
                                    ],
                                    1 => [
                                        'var' => 'enrichments.online_provider.details.trade_name.value',
                                    ],
                                    2 => 70,
                                ],
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result' => false,
                    'operator' => 'or',
                    'operands' => [
                        'operand_1' => [
                            'result' => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'Rzp Test QA Merchant',
                                'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 45,
                                'required_percentage'   => 70,
                            ],
                        ],
                        'operand_2' => [
                            'result' => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'CHIZRINZ INFOWAY PRIVATE LIMITED',
                                'operand_2' => 'RAZORPAY SOFTWARE PRIVATE LIMITED',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'       => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'     => 68,
                                'required_percentage'  => 70,
                            ],
                        ],
                    ],
                    'remarks' => [
                        'algorithm_type'      => 'fuzzy_wuzzy_default_algorithm',
                        'match_percentage'    => 68,
                        'required_percentage' => 70,
                    ],
                ],
                'error' => '',
            ],
            1 => [
                'rule' => [
                    'rule_type' => 'array_comparison_rule',
                    'rule_def' => [
                        'some' => [
                            0 => [
                                'var' => 'enrichments.online_provider.details.signatory_names',
                            ],
                            1 => [
                                'fuzzy_wuzzy' => [
                                    0 => [
                                        'var' => 'each_array_element',
                                    ],
                                    1 => [
                                        'var' => 'artefact.details.legal_name.value',
                                    ],
                                    2 => 70,
                                ],
                            ],
                        ],
                    ],
                ],
                'rule_execution_result' => [
                    'result' => false,
                    'operator' => 'some',
                    'operands' => [
                        'operand_1' => [
                            'result' => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'HARSHILMATHUR ',
                                'operand_2' => 'Rzp Test QA Merchant',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 30,
                                'required_percentage'   => 70,
                            ],
                        ],
                        'operand_2' => [
                            'result' => false,
                            'operator' => 'fuzzy_wuzzy',
                            'operands' => [
                                'operand_1' => 'Shashank kumar ',
                                'operand_2' => 'Rzp Test QA Merchant',
                                'operand_3' => 70,
                            ],
                            'remarks' => [
                                'algorithm_type'        => 'fuzzy_wuzzy_default_algorithm',
                                'match_percentage'      => 29,
                                'required_percentage'   => 70,
                            ],
                        ],
                    ],
                    'remarks' => null,
                ]
            ]
        ],
        'validation_status' => 'failed'
    ],
    'merchantDetailWithPrimaryFields' => [
        'contact_mobile' => '9308490219',
        'contact_email' => 'gauriagain.kumar+699Aug@gmail.com',
        'business_type' => '11',
        'business_name' => 'Peoplink Services Private Limited',
        'business_registered_address' => '507, Koramangala 1st block',
        'business_operation_address' => '507, Koramangala 6th block',
        'business_category'     => 'healthcare',
        'business_subcategory'  => 'clinic',
        'promoter_pan'   => 'BEGPJ7237B',
        'promoter_pan_name' => 'Anurag Joshi',
        'bank_account_number' => '019863300002403',
        'bank_account_name' => 'Shivam kumar',
        'bank_branch_ifsc' => 'PUNB0057100',
        'business_registered_city' => 'Bangalore',
        'business_registered_pin' => '560068',
        'business_registered_state' => 'Karnataka',
        'contact_name'  => 'Peoplink Services',
        'gstin_verification_status' => 'verified'
    ]


];
