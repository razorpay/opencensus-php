<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testDraft' => [
        'request'   => [
            'url'     => '/international_enablement/draft',
            'method'  => 'POST',
            'content' => [
                'goods_type'         => 'physical_goods',
                'allowed_currencies' => [
                   'INR',
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'accepts_intl_txns'                   => 0,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'products' => [
                   'payment_gateway',
                ],
                'documents' => null,
            ],
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'goods_type'         => 'physical_goods',
                'business_use_case'  => null,
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'logistic_partners'                   => null,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => null,
                'privacy_policy_link'                 => null,
                'refund_and_cancellation_policy_link' => null,
                'shipping_policy_link'                => null,
                'social_media_page_link'              => null,
                'existing_risk_checks'                => null,
                'customer_info_collected'             => null,
                'partner_details_plugins'             => null,
                'accepts_intl_txns'                   => false,
                'import_export_code'                  => null,
                'products' => [
                   'payment_gateway',
                ],
                'documents' => null,
            ],
        ],
    ],
    'testDraftUpdate' => [
        'request'   => [
            'url'     => '/international_enablement/draft',
            'method'  => 'POST',
            'content' => [
                'products' => [
                   'payment_gateway',
                ],
                'monthly_sales_intl_cards_min'        => 1500,
                'accepts_intl_txns'                   => 1,
                'contact_us_link'                     => null,
                'documents' => [
                    'ie_code' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                    'others' => [
                        'custom_type_1' => [
                            [
                                'id'           => 'doc_10000011111112',
                                'display_name' => 'display_name_2',
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'goods_type'         => 'physical_goods',
                'business_use_case'  => null,
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 1500,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'logistic_partners'                   => null,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => null,
                'terms_and_conditions_link'           => null,
                'privacy_policy_link'                 => null,
                'refund_and_cancellation_policy_link' => null,
                'shipping_policy_link'                => null,
                'social_media_page_link'              => null,
                'existing_risk_checks'                => null,
                'customer_info_collected'             => null,
                'partner_details_plugins'             => null,
                'accepts_intl_txns'                   => true,
                'import_export_code'                  => null,
                'products' => [
                   'payment_gateway',
                ],
                'documents' => [
                    'ie_code' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1'
                        ],
                   ],
                   'others' => [
                        'custom_type_1' => [
                            [
                                'id'           => 'doc_10000011111112',
                                'display_name' => 'display_name_2'
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testDraftWithValidationErrorCase1' => [
        'request'   => [
            'url'     => '/international_enablement/draft',
            'method'  => 'POST',
            'content' => [
                'goods_type'         => 'physical_goods',
                'allowed_currencies' => [
                   'INP',
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'accepts_intl_txns'                   => 0,
                'products' => [
                   'payment_gateway',
                ],
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                    '_internal' => [
                        'allowed_currencies' => [
                            'Not a valid currency: (INP)'
                        ],
                        'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                    ]
                ]
            ],
        ],
        'exception' => [
            'class'               => 'Rzp\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
        ],
    ],

    'testDraftWithValidationErrorCase2' => [
        'request'   => [
            'url'     => '/international_enablement/draft',
            'method'  => 'POST',
            'content' => [
                'goods_type'         => 'physical_goods_1',
                'business_use_case'  => 'Business Use Case',
                'allowed_currencies' => [
                   'INR',
                ],
                'about_us_link'      => 'https://www.test.com',
                'contact_us_link'    => 'https://www.razorpay2.com',
                'import_export_code' => '123456789',
                'products' => [
                   'payment_gateway',
                ],
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                    '_internal' => [
                        'goods_type' => [
                            'The selected goods type is invalid.'
                        ],
                        'business_use_case' => [
                            'The business use case must be at least 50 characters.'
                        ],
                        'contact_us_link' => [
                            'The contact us link is not a valid URL.'
                        ],
                        'import_export_code' => [
                            'The import export code must be 10 characters.'
                        ],
                        'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                    ]
                ]
            ],
        ],
        'exception' => [
            'class'               => 'Rzp\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
        ],
    ],

    'testDraftWithValidationErrorCase3' => [
        'request'   => [
            'url'     => '/international_enablement/draft',
            'method'  => 'POST',
            'content' => [
                'documents' => [
                    'ie_code' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1'
                        ],
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1'
                        ],
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1'
                        ],
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1'
                        ],
                    ],
                    'others' => [
                        'custom_type_1' => [
                            [
                                'id'           => 'doc_10000011111112',
                                'display_name' => 'display_name_2'
                            ],
                            [
                                'id'           => 'doc_10000011111112',
                                'display_name' => 'display_name_2'
                            ],
                            [
                                'id'           => 'doc_10000011111112',
                                'display_name' => 'display_name_2'
                            ],
                            [
                                'id'           => 'doc_10000011111112',
                                'display_name' => 'display_name_2'
                            ],
                        ],
                        'custom_type_2' => [
                            [
                                'id'           => '10000011111112',
                                'display_name' => 'display_name_2'
                            ],
                        ],
                    ],
                ],
                'products' => [
                   'payment_gateway',
                ],
            ],
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                    '_internal' => [
                        'documents' => [
                            'ie_code' => [
                                'The ie code may not have more than 3 items.'
                            ],
                            'others' => [
                                'custom_type_1' => [
                                    'Exceeded Max Number of Supported Documents per Type - 3'
                                ],
                                'custom_type_2' => [
                                    [
                                        'id' => [
                                            'validation.starts_with',
                                            'The id must be 18 characters.'
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                    ]
                ]
            ],
        ],
        'exception' => [
            'class'               => 'Rzp\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
        ],
    ],

    'testGetWithNoEntry' => [
        'request'   => [
            'url'     => '/international_enablement',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_NO_ENTRY_FOUND,
                ]
            ],
        ],
        'exception' => [
            'class'               => 'Rzp\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_NO_ENTRY_FOUND,
        ],
    ],

    'testGetWithDraftEntry' => [
        'request'   => [
            'url'     => '/international_enablement',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'goods_type'         => 'physical_goods',
                'business_use_case'  => null,
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'logistic_partners'                   => null,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => null,
                'privacy_policy_link'                 => null,
                'refund_and_cancellation_policy_link' => null,
                'shipping_policy_link'                => null,
                'social_media_page_link'              => null,
                'existing_risk_checks'                => null,
                'customer_info_collected'             => null,
                'partner_details_plugins'             => null,
                'accepts_intl_txns'                   => false,
                'import_export_code'                  => null,
                'products' => [
                   'payment_gateway',
                ],
                'documents' => null,
            ],
        ],
    ],

    'testGetWithSubmittedEntry' => [
        'request'   => [
            'url'     => '/international_enablement',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'goods_type'         => 'digital_services',
                'business_use_case'  => 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test',
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'logistic_partners'                   => null,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => 'https://www.test.com',
                'privacy_policy_link'                 => 'https://www.test.com',
                'refund_and_cancellation_policy_link' => 'https://www.test.com',
                'shipping_policy_link'                => null,
                'social_media_page_link'              => null,
                'existing_risk_checks'                => ['test_1'],
                'customer_info_collected'             => ['test_1'],
                'partner_details_plugins'             => ['test_1'],
                'accepts_intl_txns'                   => false,
                'import_export_code'                  => '1234567891',
                'products' => [
                   'payment_gateway',
                ],
                'documents' => [
                    'bank_statement_inward_remittance' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testDiscardWithNoEntryAndSubmittedEntry' => [
        'request'   => [
            'url'     => '/international_enablement',
            'method'  => 'DELETE',
        ],
        'response'  => [
            'status_code' => 400,
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_DISCARD,
                ]
            ],
        ],
        'exception' => [
            'class'               => 'Rzp\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_DISCARD,
        ],
    ],

    'testDiscardWithDraftEntry' => [
        'request'   => [
            'url'     => '/international_enablement',
            'method'  => 'DELETE',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'status' => 'success',
            ],
        ],
    ],

    // with digital_services and accept intl transaction set to false
    'testSubmitValidUseCase1' => [
        'request'   => [
            'url'     => '/international_enablement/submit',
            'method'  => 'POST',
            'content' => [
                'goods_type'         => 'digital_services',
                'business_use_case'  => 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test',
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'logistic_partners'                   => null,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => 'https://www.test.com',
                'privacy_policy_link'                 => 'https://www.test.com',
                'refund_and_cancellation_policy_link' => 'https://www.test.com',
                'shipping_policy_link'                => null,
                'social_media_page_link'              => null,
                'existing_risk_checks'                => ['test_1'],
                'customer_info_collected'             => ['test_1'],
                'partner_details_plugins'             => ['test_1'],
                'accepts_intl_txns'                   => 0,
                'import_export_code'                  => '1234567891',
                'products' => [
                   'payment_gateway',
                ],
                'documents' => [
                    'bank_statement_inward_remittance' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'goods_type'         => 'digital_services',
                'business_use_case'  => 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test',
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'logistic_partners'                   => null,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => 'https://www.test.com',
                'privacy_policy_link'                 => 'https://www.test.com',
                'refund_and_cancellation_policy_link' => 'https://www.test.com',
                'shipping_policy_link'                => null,
                'social_media_page_link'              => null,
                'existing_risk_checks'                => ['test_1'],
                'customer_info_collected'             => ['test_1'],
                'partner_details_plugins'             => ['test_1'],
                'accepts_intl_txns'                   => false,
                'import_export_code'                  => '1234567891',
                'products' => [
                   'payment_gateway',
                ],
                'documents' => [
                    'bank_statement_inward_remittance' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testSubmitValidUseCase2' => [
        'request'   => [
            'url'     => '/international_enablement/submit',
            'method'  => 'POST',
            'content' => [
                'goods_type'         => 'physical_goods',
                'business_use_case'  => 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test',
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'logistic_partners'                   => 'dhl',
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => 'https://www.test.com',
                'privacy_policy_link'                 => 'https://www.test.com',
                'refund_and_cancellation_policy_link' => 'https://www.test.com',
                'shipping_policy_link'                => 'https://www.test.com',
                'social_media_page_link'              => null,
                'existing_risk_checks'                => ['test_1'],
                'customer_info_collected'             => ['test_1'],
                'partner_details_plugins'             => ['test_1'],
                'accepts_intl_txns'                   => 1,
                'import_export_code'                  => '1234567891',
                'products' => [
                   'payment_links',
                   'payment_pages',
                   'invoices',
                ],
                'documents' => [
                    'bank_statement_inward_remittance' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                    'current_payment_partner_settlement_record' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                    'invoices' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                ],
            ],
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'goods_type'         => 'physical_goods',
                'business_use_case'  => 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test',
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'logistic_partners'                   => 'dhl',
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => 'https://www.test.com',
                'privacy_policy_link'                 => 'https://www.test.com',
                'refund_and_cancellation_policy_link' => 'https://www.test.com',
                'shipping_policy_link'                => 'https://www.test.com',
                'social_media_page_link'              => null,
                'existing_risk_checks'                => ['test_1'],
                'customer_info_collected'             => ['test_1'],
                'partner_details_plugins'             => ['test_1'],
                'accepts_intl_txns'                   => true,
                'import_export_code'                  => '1234567891',
                'products' => [
                   'payment_links',
                   'payment_pages',
                   'invoices',
                ],
                'documents' => [
                    'bank_statement_inward_remittance' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                    'current_payment_partner_settlement_record' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                    'invoices' => [
                        [
                            'id'           => 'doc_10000011111111',
                            'display_name' => 'display_name_1',
                        ],
                    ],
                ],
            ],
        ],
    ],

    'testSubmitWithValidationError' => [
        'request'   => [
            'url'     => '/international_enablement/submit',
            'method'  => 'POST',
            'content' => [
                'goods_type'         => 'physical_goods',
                'business_use_case'  => 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test',
                'allowed_currencies' => [
                   'INR'
                ],
                'monthly_sales_intl_cards_min'        => 2000,
                'monthly_sales_intl_cards_max'        => 4000,
                'business_txn_size_min'               => 10000,
                'business_txn_size_max'               => 20000,
                'logistic_partners'                   => null,
                'about_us_link'                       => 'https://www.test.com',
                'contact_us_link'                     => 'https://www.test.com',
                'terms_and_conditions_link'           => 'https://www.test.com',
                'privacy_policy_link'                 => 'https://www.test.com',
                'refund_and_cancellation_policy_link' => 'https://www.test.com',
                'shipping_policy_link'                => null,
                'social_media_page_link'              => null,
                'existing_risk_checks'                => ['test_1'],
                'customer_info_collected'             => ['test_1'],
                'partner_details_plugins'             => ['test_1'],
                'accepts_intl_txns'                   => 1,
                'import_export_code'                  => '1234567891',
                'products' => [
                   'payment_gateway',
                ],
                'documents' => null,
            ],
        ],
        'use_cases' => [
            'without_logistics' => [
                'response'  => [
                    'status_code' => 400,
                    'content'     => [
                        'error' => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                            '_internal' => [
                               'logistic_partners' => [
                                    'The logistic partners field is required when goods type is physical_goods.'
                                ],
                                'shipping_policy_link' => [
                                    'The shipping policy link field is required when goods type is physical_goods.'
                                ],
                                'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                            ]
                        ]
                    ],
                ],
                'exception' => [
                    'class'               => 'Rzp\Exception\BadRequestException',
                    'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                ],
            ],
            'without_intl_txn_documents' => [
                'request' => [
                    'content' => [
                        'logistic_partners'    => 'dhl',
                        'shipping_policy_link' => 'https://www.test.com',
                    ]
                ],
                'response'  => [
                    'status_code' => 200,
                    'content'     => [
                        'goods_type'         => 'physical_goods',
                        'business_use_case'  => 'test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test test',
                        'allowed_currencies' => [
                            'INR'
                        ],
                        'monthly_sales_intl_cards_min'        => 2000,
                        'monthly_sales_intl_cards_max'        => 4000,
                        'business_txn_size_min'               => 10000,
                        'business_txn_size_max'               => 20000,
                        'logistic_partners'                   => 'dhl',
                        'shipping_policy_link'                => 'https://www.test.com',
                        'about_us_link'                       => 'https://www.test.com',
                        'contact_us_link'                     => 'https://www.test.com',
                        'terms_and_conditions_link'           => 'https://www.test.com',
                        'privacy_policy_link'                 => 'https://www.test.com',
                        'refund_and_cancellation_policy_link' => 'https://www.test.com',
                        'social_media_page_link'              => null,
                        'existing_risk_checks'                => ['test_1'],
                        'customer_info_collected'             => ['test_1'],
                        'partner_details_plugins'             => ['test_1'],
                        'accepts_intl_txns'                   => true,
                        'import_export_code'                  => '1234567891',
                        'products' => [
                            'payment_gateway',
                        ],
                        'documents' => null,
                    ],
                ],
            ],
            'without_business_category_subcategory_documents' => [
                'response'  => [
                    'status_code' => 400,
                    'content'     => [
                        'error' => [
                            'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                            'description' => PublicErrorDescription::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                            '_internal' => [
                                'documents' => [
                                    'sebi_certificate' => [
                                        'The sebi certificate field is required when accepts intl txns is true.'
                                    ],
                                ],
                                'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                            ]
                        ]
                    ],
                ],
                'exception' => [
                    'class'               => 'Rzp\Exception\BadRequestException',
                    'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNATIONAL_ENABLEMENT_VALIDATION_FAILURE,
                ],
            ],
        ]
    ],

    'testGetProductInternationalStatusV2Workflow' =>[
        'request'  => [
            'url'    => '/merchants/product_international/workflow/status/all',
            'method' => 'get',
            'content' => [
                'version' => 'v2',
            ]
        ],
        'response' => [
            'content' => [
                'data' => [
                    'payment_gateway' => 'no_action_received',
                    'payment_links'   => 'in_review',
                    'payment_pages'   => 'in_review',
                    'invoices'        => 'in_review',
                ],
            ],
        ],
    ],

    'testPreviewForDraftWithoutIntlDocuments' => [
        'request'   => [
            'url'     => '/international_enablement/preview',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'enablement_progress'   => 'in_progress',
                'percentage_completion' => 77,
                'new_flow'              => true,
            ],
        ],
    ],

    'testPreviewForSubmit' => [
        'request'   => [
            'url'     => '/international_enablement/preview',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'enablement_progress'   => 'submitted',
                'percentage_completion' => 100,
                'new_flow'              => true,
            ],
        ],
    ],

    'testInternationalVisibility' => [
        'request'   => [
            'url'     => '/international_enablement/visibility',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'international_cards_enabled'               => true,
                'international_activation_form_initiated'   => false,
                'international_activation_form_completed'   => true,
                'paypal'                                    => false,
            ],
        ],
    ],

    'testInternationalVisibilityFalse' => [
        'request'   => [
            'url'     => '/international_enablement/visibility',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'international_cards_enabled'               => false,
                'international_activation_form_initiated'   => false,
                'international_activation_form_completed'   => false,
                'paypal'                                    => false,
            ],
        ],
    ],

    'testInternationalVisibilityWithoutDocuments' => [
        'request'   => [
            'url'     => '/international_enablement/visibility',
            'method'  => 'GET',
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [
                'international_cards_enabled'               => false,
                'international_activation_form_initiated'   => true,
                'international_activation_form_completed'   => false,
                'paypal'                                    => false,
            ],
        ],
    ],

    'testReminderCallbackSuccess' => [
        'request'  => [
            'url'    => '/international_enablement/reminders/live/10000000000000',
            'method' => 'POST',
        ],
        'response'  => [
            'status_code' => 200,
            'content'=>[
                'success_response' => 1,
            ],
        ]
    ],

    'testReminderCallbackFailure' => [
        'request'  => [
            'url'    => '/international_enablement/reminders/live/10000000000000',
            'method' => 'POST',
        ],
        'response'  => [
            'status_code' => 400,
            'content'=>[
                'error_response' => 1,
            ],
        ]
    ],
];
