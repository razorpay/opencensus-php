<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Exception\BadRequestException;
use RZP\Tests\Functional\Fixtures\Entity\Org;
use RZP\Models\Merchant\Detail\RejectionReasons as RejectionReasons;

return [

    'testGetMerchantDetails' => [
        'request'  => [
            'url'    => '/merchant/activation',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                "documents"    => [
                    'Address_proof_url' => [
                        [
                            "id"            => "DM6dWd1tzUfbnM",
                            "file_store_id" => "DM6dXJfU4WzeAF",
                        ],
                    ],
                ],
                'can_submit'   => false,
                'merchant'     => [
                    'activated' => false,
                    'live'      => false,
                ]
            ],
        ],
    ],

    'testGetMerchantSupportedPlugins' => [
        'request'  => [
            'url'    => '/onboarding/merchant/supported_plugins',
            'method' => 'GET'
        ],
        'response' => [
            "content" => [
                [
                    "name"              => "Arastta",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/Arastta.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/arastta/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "EasyDigitalDownloads",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/EasyDigitalDownload.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/easy-digital-downloads/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "CS-Cart",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/CSCart.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/cs-cart/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "gravityforms",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/GravityForms.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/gravity-forms",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "Magento",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/Magento.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/magento/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "OpenCart",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/OpenCart.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/open-cart/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "PrestaShop",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/Prestashop.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/prestashop/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "Shopify",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/Shopify.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/shopify/",
                    "integration_url"   => "https://www.google.com/admin/settings/payments/alternative-providers/1058839"
                ],
                [
                    "name"              => "WHMCS",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/WHMCS.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/whmcs/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "Wix",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/Wix.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/wix/",
                    "integration_url"   => "https://support.wix.com/en/article/connecting-razorpay-as-a-payment-provider"
                ],
                [
                    "name"              => "WooCommerce",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/WooCommerce.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/woocommerce/",
                    "integration_url"   => ""
                ],
                [
                    "name"              => "WordPress",
                    "icon"              => "https://cdn.razorpay.com/static/assets/product-led-onboarding/Wordpress.svg",
                    "integration_guide" => "https://razorpay.com/docs/payments/payment-gateway/ecommerce-plugins/wordpress/",
                    "integration_url"   => ""
                ]
            ]
        ]
    ],


'testIsAdminLoggedInAsMerchant' => [
        'request'  => [
            'url'    => '/merchant/is_admin_as_merchant',
            'method' => 'GET',
            'headers'=> [ 'X-Dashboard-AdminLoggedInAsMerchant' => 'true' ],
        ],
        'response' => [
            'content' => [
                'is_admin_as_merchant' =>  true,
            ],
        ],
    ],

    'testGetMerchantPlugin' => [
        'request'  => [
            'url'    => '/onboarding/merchants/10000000000156/plugin',
            'method' => 'GET',
        ],
        'response' => [
            'content' =>
                [
                    [
                        'website'                  => 'www.google.com',
                        'merchant_selected_plugin' => 'shopify',
                        'suggested_plugin'         => 'whmcs'
                    ]
                ],
        ],
    ],

    'testIfSubMerchant' => [
        'request'  => [
            'url'    => '/merchant/activation',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'isSubMerchant' => true,
            ],
        ],
    ],

    'testCanAccessActivationFormRoute' => [
        'request'  => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC0000002'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'headers'=> [ 'X-Dashboard-AdminLoggedInAsMerchant' => 'true' ],
        ],
        'response' => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC0000002',
                'verification'              => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'                => false,
            ],
        ],
    ],

    'testActivationFormRouteBlockedByFeature' => [
        'request'  => [
            'content' => [
                'business_registered_state' => 'JAMMU AND KASHMIR',
                'bank_branch_ifsc'          => 'ICIC0000002'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response'  =>[
            'content' =>[
                'error'  => [
                    'code' =>  ErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_URL_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testUpdateIfscCode' => [
        'request'  => [
            'content' => [
                'business_registered_state' => 'JAMMU AND KASHMIR',
                'bank_branch_ifsc'          => 'ICIC0000002'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC0000002',
                'business_registered_state' => 'JK',
                'verification'              => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'                => false,
            ],
        ],
    ],

    'testSubmit' => [
        'request'  => [
            'content' => [
                "submit"=>"1",
                "company_cin" => "U67190TN2014PTC096971",
                "gstin"=>"03AADCB1234M1ZX"
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'submitted'    => true,
                'verification' => [
                    'status' => 'pending'
                ],
                'can_submit'   => true,
                'locked'       => true,
            ],
        ],
    ],

    'testSubmitAutoActivate' => [
        'request'  => [
            'content' => [
                'bank_account_name'   => 'Test',
                'bank_account_number' => '111000',
                'bank_branch_ifsc'    => 'SBIN0007105',
                'bank_account_type'   => 'savings',
                'business_name'       => 'Test',
                'business_type'       => 1,
                'submit'              => true,
                'business_category'   => 'financial_services',
                'business_subcategory'=> 'accounting',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'submitted'      => true,
                'verification'   => [
                    'status' => 'pending'
                ],
                'can_submit'     => true,
                'activated'      => 1,
                'locked'         => true,
                'auto_activated' => true
            ],
        ],
    ],

    'testSubmitWithInvalidFields' => [
        'request' => [
            'content' => [
                'submit' => true
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUpdateIfscCodeWithFailure' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IFSC Code',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateEmail' => [
        'request' => [
            'content' => [
                'transaction_report_email' => 'a.b@c.com,a.c@d.com'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'transaction_report_email' => 'a.b@c.com,a.c@d.com',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUpdateEmails' => [
        'request' => [
            'content' => [
                'transaction_report_email' => 'a.b@c.com,a.c'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The provided transaction report email is invalid: a.c',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateEmailWithFailure' => [
        'request' => [
            'content' => [
                'transaction_report_email' => 'a.b'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The provided transaction report email is invalid: a.b',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateDetailForLockedMerchant' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC0000001'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Merchant activation form has been locked for editing by admin.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_DETAIL_ALREADY_LOCKED,
        ],
    ],

    'testGetMerchantRejectionReasons' => [
        'request' => [
            'content' => [],
            'url'     => '/merchant/activation/rejection_reasons',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                RejectionReasons::RISK_RELATED_REJECTIONS => [
                    [
                        RejectionReasons::CODE        => RejectionReasons::DEDUPE_BLOCKED,
                        RejectionReasons::DESCRIPTION => RejectionReasons::DEDUPE_BLOCKED_DESCRIPTION,
                    ],
                ],
                RejectionReasons::PROHIBITED_BUSINESS => [
                    [
                        RejectionReasons::CODE        => RejectionReasons::GET_RICH_SCHEMES,
                        RejectionReasons::DESCRIPTION => RejectionReasons::GET_RICH_SCHEMES_DESCRIPTION,
                    ],
                ],
                RejectionReasons::UNREG_BLACKLIST => [
                    [
                        RejectionReasons::CODE        => RejectionReasons::UNREG_FINANCIAL_SERVICES,
                        RejectionReasons::DESCRIPTION => RejectionReasons::UNREG_FINANCIAL_SERVICES_DESCRIPTION,
                    ],
                ],
            ],
        ],
    ],

    'testLockMerchant' => [
        'request' => [
            'content' => [
                'locked' => true
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'locked' => true,
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testAddClarificationReasons' => [
        'request' => [
            'content' =>  [
                "clarification_reasons" => [
                    [
                        "group_name" => "bank_details",
                        "field_details" => [
                            "bank_account_number"=>"1234567891",
                            "bank_account_name"=>"test",
                            "bank_branch_ifsc"=>"icic0001231",
                            "cancelled_cheque"=>null
                            ],
                        "comment_data" => [
                            "type"=>"predefined",
                            "text"=>"bank_account_change_request_for_prop_ngo_trust"
                            ]
                    ]
                ],
                "old_clarification_reasons" => [
                    "issue_fields_reason"=> "Reason Details",
                    "internal_notes"=> "Internal notes",
                    "issue_fields" => "bank_account_number,bank_account_name,bank_branch_ifsc",
                    "kyc_clarification_reasons" => [
                        "clarification_reasons" => [
                            "bank_account_number" => [
                                [
                                    "reason_type" => "predefined",
                                    "field_value" => "123456780",
                                    "reason_code" => "bank_account_change_request_for_unregistered"
                                ]
                            ],
                            "bank_account_name" => [
                                [
                                    "reason_type" => "predefined",
                                    "field_value" => "Ajay Kumar Brahma",
                                    "reason_code" => "bank_account_change_request_for_unregistered"
                                ]
                            ],
                            "bank_branch_ifsc" => [
                                [
                                    "reason_type" => "predefined",
                                    "field_value" => "SBIN0000202",
                                    "reason_code" => "bank_account_change_request_for_unregistered"
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'clarification_details' => [
                    'nc_count' => 0,
                    'bank_details' => [
                        'nc_count' => 1,
                        'status' => "needs_clarification",
                        'fields' => ["bank_branch_ifsc", "cancelled_cheque", "bank_account_name", "bank_account_number"],
                        'comments' => [
                            [
                                'status' => "needs_clarification",
                                'nc_count' => 1,
                                'comment_data' => [
                                    'type' => "predefined",
                                    'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details.",
                                    ],
                                'message_from' => "admin",
                                'admin_email' => 'admin@razorpay.com',
                                'field_details' => [
                                    'bank_branch_ifsc' => "icic0001231",
                                    'cancelled_cheque' => NULL,
                                    'bank_account_name' => "test",
                                    'bank_account_number' => "1234567891",
                                ],
                            ],
                        ],
                    ],
                ],
                'kyc_clarification_reasons' => [
                    'clarification_reasons' => [
                        'bank_account_number' => [
                            [
                                'reason_type' => "predefined",
                                'field_value' => "123456780",
                                'reason_code' => "bank_account_change_request_for_unregistered",
                                'from' => "admin",
                                'nc_count' => 1,
                                'is_current' =>  TRUE,
                            ],
                        ],
                        'bank_account_name' => [
                            [
                                'reason_type' => "predefined",
                                'field_value' => "Ajay Kumar Brahma",
                                'reason_code' => "bank_account_change_request_for_unregistered",
                                'from' => "admin",
                                'nc_count' => 1,
                                'is_current' =>  TRUE,
                            ],
                        ],
                        'bank_branch_ifsc' => [
                            [
                                'reason_type' => "predefined",
                                'field_value' => "SBIN0000202",
                                'reason_code' => "bank_account_change_request_for_unregistered",
                                'from' => "admin",
                                'nc_count' => 1,
                                'is_current' =>  TRUE,
                            ],
                        ],
                    ],
                    'clarification_reasons_v2' => [
                        'bank_account_number' => [
                            [
                                'reason_type' => "predefined",
                                'field_value' => "123456780",
                                'reason_code' => "bank_account_change_request_for_unregistered",
                                'from' => "admin",
                                'nc_count' => 1,
                                'is_current' =>  TRUE,
                            ],
                        ],
                    ],
                    'nc_count' => 1,
                ],
            ],
        ],
    ],

    'testAddClarificationReasonsNullFields' => [
        'request' => [
            'content' =>  [
                "clarification_reasons" => [
                    [
                        "group_name" => "bank_details",
                        "field_details" => [
                            "bank_account_number"=>null,
                            "bank_account_name"=>null,
                            "bank_branch_ifsc"=>null,
                            "cancelled_cheque"=>null
                        ],
                        "comment_data" => [
                            "type"=>"predefined",
                            "text"=>"bank_account_change_request_for_prop_ngo_trust"
                            ]
                    ]
                ],
                "old_clarification_reasons" => [
                    "issue_fields_reason"=> "Reason Details",
                    "internal_notes"=> "Internal notes",
                    "issue_fields" => "bank_account_number,bank_account_name,bank_branch_ifsc",
                    "kyc_clarification_reasons" => [
                        "clarification_reasons" => [
                            "bank_account_number" => [
                                [
                                    "reason_type" => "predefined",
                                    "field_value" => "123456780",
                                    "reason_code" => "bank_account_change_request_for_unregistered"
                                ]
                            ],
                            "bank_account_name" => [
                                [
                                    "reason_type" => "predefined",
                                    "field_value" => "Ajay Kumar Brahma",
                                    "reason_code" => "bank_account_change_request_for_unregistered"
                                ]
                            ],
                            "bank_branch_ifsc" => [
                                [
                                    "reason_type" => "predefined",
                                    "field_value" => "SBIN0000202",
                                    "reason_code" => "bank_account_change_request_for_unregistered"
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'clarification_details' => [
                    'nc_count' => 0,
                    'bank_details' => [
                        'nc_count' => 1,
                        'status' => "needs_clarification",
                        'fields' => ["bank_branch_ifsc", "cancelled_cheque", "bank_account_name", "bank_account_number"],
                        'comments' => [
                            [
                                'status' => "needs_clarification",
                                'admin_email' => 'admin@razorpay.com',
                                'nc_count' => 1,
                                'comment_data' => [
                                    'type' => "predefined",
                                    'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details.",
                                ],
                                'message_from' => "admin",
                                'field_details' => null,
                            ],
                        ],
                    ],
                ]
            ],
        ],
    ],

    'testAddNonGroupClarificationReasons' => [
        'request' => [
            'content' =>  [
                "clarification_reasons" =>  [
                    [
                        "group_name" => "website",
                        "field_details" =>  ["website" =>  "https://www.hello.com"],
                        "comment_data" =>  [
                            "type" =>  "custom",
                            "text" =>  "your website is not live"
                        ]
                    ]
                ],
                "old_clarification_reasons" => [
                    "issue_fields_reason"=> "Reason Details",
                    "internal_notes"=> "Internal notes",
                    "issue_fields" => "website",
                    "kyc_clarification_reasons" => [
                        "clarification_reasons" => [
                            "website" => [
                                [
                                    "reason_type" => "custom",
                                    "field_value" => "https://www.hello.com",
                                    "reason_code" => "your website is not live"
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                "clarification_details" => [
                    "nc_count" => 0,
                    "website" => [
                        'nc_count' => 1,
                        "status" => "needs_clarification",
                        "fields" => [
                            "website"
                        ],
                        "comments" => [
                            [
                                "field_details" => [
                                    "website" => "https://www.hello.com"
                                ],
                                "comment_data" => [
                                    "type" => "custom",
                                    "text" => "your website is not live"
                                ],
                                "message_from" => "admin",
                                'admin_email' => 'admin@razorpay.com',
                                'nc_count' => 1,
                                "status" => "needs_clarification"
                            ]
                        ]
                    ]
                ],
                ],
            ],
    ],

    'testAddNonGroupClarificationReasonsForMerchantInNC' => [
        'request' => [
            'content' =>  [
                "clarification_reasons" =>  [
                    [
                        "group_name" => "website",
                        "field_details" =>  ["website" =>  "https://www.hello.com"],
                        "comment_data" =>  [
                            "type" =>  "custom",
                            "text" =>  "your website is not live"
                        ]
                    ]
                ],
                "old_clarification_reasons" => [
                    "issue_fields_reason"=> "Reason Details",
                    "internal_notes"=> "Internal notes",
                    "issue_fields" => "website",
                    "kyc_clarification_reasons" => [
                        "clarification_reasons" => [
                            "website" => [
                                [
                                    "reason_type" => "custom",
                                    "field_value" => "https://www.hello.com",
                                    "reason_code" => "your website is not live"
                                ]
                            ]
                        ]
                    ]
                ]
            ],
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testGetClarificationReasons' => [
        'request' => [
            'url'       => '/merchant/activation/clarifications',
            'method'    => 'GET'
        ],
        'response' => [
            'content' => [
                'clarification_details' =>  [
                    'nc_count' => 0,
                    'bank_details' =>  [
                        'fields' =>  [
                            "bank_branch_ifsc",
                            "cancelled_cheque",
                            "bank_account_name",
                            "bank_account_number"
                        ],
                        'status' => "needs_clarification",
                        'nc_count' => 1,
                        'comments' =>  [
                            [
                                'status' => "needs_clarification",
                                'nc_count' => 1,
                                'comment_data' =>  [
                                    'type' => "predefined",
                                    'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details."
                                ],
                                'message_from' => "admin",
                                'admin_email' => 'admin@razorpay.com',
                                'field_details' => NULL,
                            ],
                            [
                                'status' => "needs_clarification",
                                'nc_count' => 1,
                                'comment_data' =>  [
                                    'type' => "predefined",
                                    'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details."
                                ],
                                'message_from' => "admin",
                                'admin_email' => 'admin@razorpay.com',
                                'field_details' =>  [
                                    'bank_branch_ifsc' => "icic0001231",
                                    'cancelled_cheque' => NULL,
                                    'bank_account_name' => "test",
                                    'bank_account_number' => "1234567891"
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],

    'changeActivationStatusToNeedsClarification' => [
        'request'  => [
            'content' => [
                'activation_status' => 'needs_clarification',
            ],
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status' => 'needs_clarification',
            ],
        ],
    ],
    'changeActivationStatusToUnderReview' => [
        'request'  => [
            'content' => [
                'activation_status' => 'under_review',
            ],
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status' => 'under_review',
            ],
        ],
    ],
    'changeActivationStatusToNeedsClarificationWithoutReasons' => [
        'request'  => [
            'content' => [
                'activation_status' => 'needs_clarification',
            ],
            'method'  => 'PATCH'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testSaveGroupCommentsMerchantClarificationReasons' => [
        'request' => [
            'url'       => '/merchant/activation/clarifications',
            'method'    => 'POST',
            'content' => [
                "bank_details"=> [
                    "comment_data"=> [
                        "type"=> "custom",
                        "text"=> "Bank details are correct. Please check again"
                    ]
                ]
            ],
        ],
        'response' => [
            'content' => [
                    "clarification_details"=> [
                        'nc_count' => 1,
                        'bank_details' => [
                            'status' => "needs_clarification",
                            'nc_count' => 1,
                            'fields' => [
                                "bank_branch_ifsc",
                                "cancelled_cheque",
                                "bank_account_name",
                                "bank_account_number"
                            ],
                            'comments' => [
                                [
                                    "field_details"=> null,
                                    "comment_data"=> [
                                        "text"=> "Bank details are correct. Please check again",
                                        "type"=> "custom"
                                    ],
                                    "message_from"=> "merchant",
                                    'nc_count' => 1,
                                    "status"=> "needs_clarification",
                                ],
                                [
                                    'status' => "needs_clarification",
                                    'nc_count' => 1,
                                    'comment_data' => [
                                        'type' => "predefined",
                                        'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details.",
                                    ],
                                    'message_from' => "admin",
                                    'admin_email' => 'admin@razorpay.com',
                                    'field_details' => [
                                        'bank_branch_ifsc' => "icic0001231",
                                        'cancelled_cheque' => NULL,
                                        'bank_account_name' => "test",
                                        'bank_account_number' => "1234567891",
                                    ],
                                ],
                            ],
                        ],
                    ]
            ],
        ],
        ],

    'testSaveGroupMerchantClarificationReasonsDocValidation' => [
        'request' => [
            'url'       => '/merchant/activation/clarifications',
            'method'    => 'POST',
            'content' => [
                "bank_details"=> [
                    "field_details"=> [
                        "bank_branch_ifsc"=> "icic0001232",
                        "cancelled_cheque"=> "Km8g59o82Gw6IA",
                        "bank_account_name"=> "test",
                        "bank_account_number"=> "1234567892",
                    ],
                    "submit"=> 1
                ],
            ],
            'response' => [
                'status_code' => 400,
                ]
        ]
    ],

    'testSaveGroupMerchantClarificationReasonsMissingField' => [
        'request' => [
            'url'       => '/merchant/activation/clarifications',
            'method'    => 'POST',
            'content' => [
                "bank_details"=> [
                    "field_details"=> [
                        "bank_branch_ifsc"=> "icic0001232",
                        "cancelled_cheque"=> "Km8g59o82Gw6IA",
                        "bank_account_number"=> "1234567892",
                    ],
                    "submit"=> 1
                ],
            ]],'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testSaveGroupMerchantClarificationReasonsInvalidFeildData' => [
        'request' => [
            'url'       => '/merchant/activation/clarifications',
            'method'    => 'POST',
            'content' => [
                "bank_details"=> [
                    "field_details"=> [
                        "bank_branch_ifsc"=> "icic0001232rfhhggjhghgjkhjkhjhghjghj",
                        "cancelled_cheque"=> "Km8g59o82Gw6IA",
                        "bank_account_name"=> "test",
                        "bank_account_number"=> "1234567892",
                    ],
                    "submit"=> 1
                ],
            ]],'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testSaveGroupMerchantClarificationReasons' => [
        'request' => [
            'url'       => '/merchant/activation/clarifications',
            'method'    => 'POST',
            'content' => [
                "bank_details"=> [
                    "field_details"=> [
                        "bank_branch_ifsc"=> "icic0001232",
                        "cancelled_cheque"=> "Km8g59o82Gw6IA",
                        "bank_account_name"=> "test",
                        "bank_account_number"=> "1234567892",
                    ],
                    "submit"=> 1
                ],
                ]],
        'response' => [
            'content' => [
                "clarification_details"=> [
                                              'nc_count' => 1,
                                              'bank_details' => [
                                                  'nc_count' => 1,
                                                  'status' => "submitted",
                                                  'fields' => [
                                                      "bank_branch_ifsc",
                                                      "cancelled_cheque",
                                                      "bank_account_name",
                                                      "bank_account_number"
                                                  ],
                                                  'comments' => [
                                                      [
                                                          "field_details"=> [
                                                              "bank_branch_ifsc"=> "icic0001232",
                                                              "cancelled_cheque"=> "Km8g59o82Gw6IA",
                                                              "bank_account_name"=> "test",
                                                              "bank_account_number"=> "1234567892",

                                                          ],
                                                          "comment_data"=> null,
                                                          "message_from"=> "merchant",
                                                          'nc_count' => 1,
                                                          "status"=> "submitted",
                                                      ],
                                                      [
                                                          "field_details"=> null,
                                                          "comment_data"=> [
                                                              "text"=> "Bank details are correct. Please check again",
                                                              "type"=> "custom"
                                                          ],
                                                          "message_from"=> "merchant",
                                                          'nc_count' => 1,
                                                          "status"=> "submitted",
                                                      ],
                                                      [
                                                          'status' => "submitted",
                                                          'nc_count' => 1,
                                                          'comment_data' => [
                                                              'type' => "predefined",
                                                              'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details.",
                                                          ],
                                                          'message_from' => "admin",
                                                          'admin_email' => 'admin@razorpay.com',
                                                          'field_details' => [
                                                              'bank_branch_ifsc' => "icic0001231",
                                                              'cancelled_cheque' => NULL,
                                                              'bank_account_name' => "test",
                                                              'bank_account_number' => "1234567891",
                                                          ],
                                                      ],
                                                  ],
                                              ],
                                          ],
                ],
            ]
    ],

    'testSaveNotesForGroupClarifications' => [
            'request' => [
                'content' => [
                    "bank_details"=> [
                        "comment_data"=> [
                            "type"=> "note",
                            "text"=> "updated new set of bank details. please verify now"
                        ]
                    ]
                ],
            ],
            'response' => [
                'content' => [
                    "clarification_details"=> [
                                                  'nc_count' => 1,
                                                  'bank_details' => [
                                                      'nc_count' => 1,
                                                      'status' => "submitted",
                                                      'fields' => [
                                                          "bank_branch_ifsc",
                                                          "cancelled_cheque",
                                                          "bank_account_name",
                                                          "bank_account_number"
                                                      ],
                                                      'comments' => [
                                                          [
                                                              "field_details"=> null,
                                                              "comment_data"=> [
                                                                  "text"=> "updated new set of bank details. please verify now",
                                                                  "type"=> "note"
                                                              ],
                                                              "message_from"=> "merchant",
                                                              'nc_count' => 1,
                                                              "status"=> "submitted",
                                                          ],
                                                          [
                                                              "field_details"=> [
                                                                  "bank_branch_ifsc"=> "icic0001232",
                                                                  "cancelled_cheque"=> "Km8g59o82Gw6IA",
                                                                  "bank_account_name"=> "test",
                                                                  "bank_account_number"=> "1234567892",

                                                              ],
                                                              "comment_data"=> null,
                                                              "message_from"=> "merchant",
                                                              'nc_count' => 1,
                                                              "status"=> "submitted",
                                                          ],
                                                          [
                                                              "field_details"=> null,
                                                              "comment_data"=> [
                                                                  "text"=> "Bank details are correct. Please check again",
                                                                  "type"=> "custom"
                                                              ],
                                                              "message_from"=> "merchant",
                                                              'nc_count' => 1,
                                                              "status"=> "submitted",
                                                          ],
                                                          [
                                                              'status' => "submitted",
                                                              'comment_data' => [
                                                                  'type' => "predefined",
                                                                  'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details.",
                                                              ],
                                                              'message_from' => "admin",
                                                              'admin_email' => 'admin@razorpay.com',
                                                              'nc_count' => 1,
                                                              'field_details' => [
                                                                  'bank_branch_ifsc' => "icic0001231",
                                                                  'cancelled_cheque' => NULL,
                                                                  'bank_account_name' => "test",
                                                                  'bank_account_number' => "1234567891",
                                                              ],
                                                          ],
                                                      ],
                                                  ],
                                              ],
                ]
            ]
        ],

    'testSubmitNCFormGroupFields' => [
        'request' => [
            'content' => [
                "submit"=> '1'
            ],
        ],
        'response' => [
            'content' => [
                "clarification_details"=> [
                    'nc_count' => 1,
                    'bank_details' => [
                        'status' => "under_review",
                        "nc_count"=> 1,
                        'fields' => [
                            "bank_branch_ifsc",
                            "cancelled_cheque",
                            "bank_account_name",
                            "bank_account_number"
                        ],
                        'comments' => [
                            [
                                "field_details"=> null,
                                "comment_data"=> [
                                    "text"=> "updated new set of bank details. please verify now",
                                    "type"=> "note"
                                ],
                                "message_from"=> "merchant",
                                "nc_count"=> 1,
                                "status"=> "under_review",
                            ],
                            [
                                "field_details"=> [
                                    "bank_branch_ifsc"=> "icic0001232",
                                    "cancelled_cheque"=> "Km8g59o82Gw6IA",
                                    "bank_account_name"=> "test",
                                    "bank_account_number"=> "1234567892",

                                ],
                                "comment_data"=> null,
                                "message_from"=> "merchant",
                                "nc_count"=> 1,
                                "status"=> "under_review",
                            ],
                            [
                                "field_details"=> null,
                                "comment_data"=> [
                                    "text"=> "Bank details are correct. Please check again",
                                    "type"=> "custom"
                                ],
                                "message_from"=> "merchant",
                                "nc_count"=> 1,
                                "status"=> "under_review",
                            ],
                            [
                                'status' => "under_review",
                                'comment_data' => [
                                    'type' => "predefined",
                                    'text' => "Entered bank details are incorrect, please share company bank account details or authorised signatory details.",
                                ],
                                'message_from' => "admin",
                                'admin_email' => 'admin@razorpay.com',
                                "nc_count"=> 1,
                                'field_details' => [
                                    'bank_branch_ifsc' => "icic0001231",
                                    'cancelled_cheque' => NULL,
                                    'bank_account_name' => "test",
                                    'bank_account_number' => "1234567891",
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        ]
    ],

    'testSubmitNCFormNonGroupFields' => [
        'request' => [
            'content' => [
                "submit"=> '1'
            ],
        ],
        'response' => [
            'content' => [
                "clarification_details"=> [
                    "nc_count"=> 1,
                    "website"=> [
                        "fields"=> [
                            "website"
                        ],
                        "status"=> "under_review",
                        "nc_count"=> 1,
                        "comments"=> [
                            [
                                "field_details"=> null,
                                "comment_data"=> [
                                    "type"=> "note",
                                    "text"=> "website is going live this month"
                                ],
                                "message_from"=> "merchant",
                                "nc_count"=> 1,
                                "status"=> "under_review",
                            ],
                            [
                                "field_details"=> null,
                                "comment_data"=> [
                                    "type"=> "custom",
                                    "text"=> "website is in progress of going live"
                                ],
                                "message_from"=> "merchant",
                                "nc_count"=> 1,
                                "status"=> "under_review",
                            ],
                            [
                                "field_details"=> [
                                    "website"=> "https://www.hello.com"
                                ],
                                "comment_data"=> [
                                    "type"=> "custom",
                                    "text"=> "your website is not live"
                                ],
                                "message_from"=> "admin",
                                "nc_count"=> 1,
                                'admin_email' => 'admin@razorpay.com',
                                "status"=> "under_review",
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],

    'testSubmitNCFormNonGroupFieldsWithoutGroupSubmission' => [
        'request' => [
            'content' => [
                "submit"=> '1'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ]
    ],

    'testAddCommentForNonGroupFieldsClarification' => [
            'request' => [
                'content' => [
                    "website"=> [
                        "comment_data"=> [
                            "type"=> "custom",
                            "text"=> "website is in progress of going live"
                        ]
                    ]
                ],
            ],
            'response' => [
                'content' => [
                    "clarification_details"=> [
                        "nc_count"=> 1,
                        "website"=> [
                            "fields"=> [
                                "website"
                            ],
                            "nc_count"=> 1,
                            "status"=> "needs_clarification",
                            "comments"=> [
                                [
                                    "field_details"=> null,
                                    "comment_data"=> [
                                        "type"=> "custom",
                                        "text"=> "website is in progress of going live"
                                    ],
                                    "message_from"=> "merchant",
                                    "nc_count"=> 1,
                                    "status"=> "needs_clarification",
                                ],
                                [
                                    "field_details"=> [
                                        "website"=> "https://www.hello.com"
                                    ],
                                    "comment_data"=> [
                                        "type"=> "custom",
                                        "text"=> "your website is not live"
                                    ],
                                    "message_from"=> "admin",
                                    'admin_email' => 'admin@razorpay.com',
                                    "nc_count"=> 1,
                                    "status"=> "needs_clarification",
                                ]
                            ]
                        ]
                    ]
                ]
                ]
            ],

    'testSubmitForNonGroupFieldsClarification' => [
            'request' => [
                'content' => [
                    "website"=> [
                        "submit"=> 1
                    ]
                ],
            ],
            'response' => [
                'content' => [
                    "clarification_details"=> [
                        "nc_count"=> 1,
                        "website"=> [
                            "status"=> "submitted",
                            "nc_count"=> 1,
                            "fields"=> [
                                "website"
                            ],
                            "comments"=> [
                                [
                                    "field_details"=> null,
                                    "comment_data"=> [
                                        "type"=> "custom",
                                        "text"=> "website is in progress of going live"
                                    ],
                                    "nc_count"=> 1,
                                    "message_from"=> "merchant",
                                    "status"=> "submitted",
                                ],
                                [
                                    "field_details"=> [
                                        "website"=> "https://www.hello.com"
                                    ],
                                    "comment_data"=> [
                                        "type"=> "custom",
                                        "text"=> "your website is not live"
                                    ],
                                    "message_from"=> "admin",
                                    "nc_count"=> 1,
                                    'admin_email' => 'admin@razorpay.com',
                                    "status"=> "submitted",
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],

    'testAddNoteForNonGroupFieldsClarification' => [
            'request' => [
                'content' => [
                    "website"=> [
                        "comment_data"=> [
                            "type"=> "note",
                            "text"=> "website is going live this month"
                        ]
                    ]
                ],
            ],
            'response' => [
                'content' => [
                    "clarification_details"=> [
                        "nc_count"=> 1,
                        "website"=> [
                            "fields"=> [
                                "website"
                            ],
                            "nc_count"=> 1,
                            "status"=> "submitted",
                            "comments"=> [
                                [
                                    "field_details"=> null,
                                    "comment_data"=> [
                                        "type"=> "note",
                                        "text"=> "website is going live this month"
                                    ],
                                    "message_from"=> "merchant",
                                    "nc_count"=> 1,
                                    "status"=> "submitted",
                                ],
                                [
                                    "field_details"=> null,
                                    "comment_data"=> [
                                        "type"=> "custom",
                                        "text"=> "website is in progress of going live"
                                    ],
                                    "message_from"=> "merchant",
                                    "nc_count"=> 1,
                                    "status"=> "submitted",
                                ],
                                [
                                    "field_details"=> [
                                        "website"=> "https://www.hello.com"
                                    ],
                                    "comment_data"=> [
                                        "type"=> "custom",
                                        "text"=> "your website is not live"
                                    ],
                                    "message_from"=> "admin",
                                    'admin_email' => 'admin@razorpay.com',
                                    "nc_count"=> 1,
                                    "status"=> "submitted",
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ],

    'testGetMerchantActivationStatusChangeLog' => [
        'request' => [
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'entity_type' => 'merchant_detail',
                        'name'        => 'under_review',
                    ],
                    [
                        'entity_type' => 'merchant_detail',
                        'name'        => 'activated',
                    ],
                ],
            ],
        ],
    ],

    'testMerchantFormArchive' => [
        'request' => [
            'content' => [
                'archive' => 1,
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'archived' => 1,
            ],
        ],
    ],

    'testMerchantActivationStatus' => [
        'request' => [
            'content' => [
                'activation_status'  => 'under_review',
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status'  => 'under_review',
            ],
        ],
    ],

    'testGetNCAdditionalDocuments' => [
    'request'  => [
        'url'    => '/merchant/activation/document_types',
        'method' => 'GET'
    ],
    'response' => [
        'content' => [

            "fssai_certificate"                 => "FSSAI certificate",
            "ayush_certificate"                 => "Ayush certificate",
            "sebi_registration_certificate"     => "SEBI Registration Certificate",
            "ffmc_license"                      => "FFMC License",
            "form_12a_url"                      => "Form 12A Allotment Letter",
            "form_80g_url"                      => "Form 80G Allotment Letter",
            "nbfc_registration_certificate"     => "NBFC Registration Certificatee",
            "bis_certificate"                   => "BIS certificate",
            "irda_certificate"                  => "IRDA certificate",
            "amfi_certificate"                  => "AMFI Certificate",
            "iata_certificate"                  => "IATA Certificate",
            "fda_certificate"                   => "FDA certificate",
            "dot_certificate"                   => "DOT certificate",
            "trai_certificate"                  => "TRAI certificate",
            "rbi_certificate"                   => "RBI certificate",
            "dgca_certificate"                   => "DGCA certificate",
            "national_housing_bank_certificate" => "Certificate issued by National Housing Bank",
            "affiliation_certificate"           => "Affiliation Certificate",
            "dealership_rights_certificate"     => "Dealership rights",
            "pci_dss_certificate"               => "AOC/Certificate PCI-DSS",
            "gii_certificate"                   => "GII certificate",
            "pharmacy_drug_license"             => "Pharmacy or Retail/wholesale Drug License",
            "form_20_20b_21_21b"                => "Form 20/21/20B/21B",
            "invoice"                           => "Invoice",
            "sla_dealership_agreement"          => "SLA/Dealership agreement",
            "reseller_agreement"               => "Re-seller agreement",
            "liquor_license"                    => "Brewery addendum or Liquor license",
            "form_8a"                           => "Form 8A",
            "form_10ac"                         => "Form 10AC",
            "irctc_agent_agreement"             => "IRCTC agent agreement",
            "undertaking"                       => "Undertaking",
            "epf_scheme_certificate"            => "EPF scheme certificate",
            "proof_of_profession"               => "Proof of profession",
            "gia_certificate"                   => "GIA certificate",
            "pm_wani_certificate"               => "PM WANI certificate",
            "peso_license"                     => "PESO license",
            "domain_ownership_document"         => "Domain ownership invoice / Self Declaration / AOC for PCI DSS",
            "iec_license"                       => "IEC license",
            "business_correspondent_document"   => "Business correspondent document",
            "mmtc_pamp_license"                 => "MMTC PAMP license",
            "safegold_partnership_document"     => "Partnership document with safegold",
            "ppi_license"                       => "PPI license ( open and semi-closed )",
            "brand_tie_up_document"             => "Tie-up with brands for gift Vouchers/Paper",
            "fda_license"                       => "FDA license",
            "fssai_license"                     => "FSSAI license",
            "merchant_service_agreement"        => "Merchant Service Agreement (MSA)",
            "legal_opinion_document"            => "Legal Opinion from Legal firm",
            "undertaking_document"              => "Undertaking document",
            "manufacturing_license"             => "Manufacturing License",
            "sla_document"                      => "Service level Agreement/tie-up document",
            "rera_license"                      => "RERA License",
            "copywrite_license"                 => "Copywrite License",
            "trade_license"                     => "Trade license",
            "iato_license"                      => "IATO license",
            "bbps_document"                     => "BBPS document",
            "mso_document"                      => "MSO/ Local cable opertor",
            "govt_authorisation_letter"         => "Govt authorisation Letter",
            "cpv_report"                        => "CPV report",
            "board_resolution_letter"           => "Board Resolution Letter"
        ]
    ],
],

    'testPgKycActivation' => [
        'request' => [
            'content' => [
                'activation_status'  => 'activated',
            ],
            'method' => 'PATCH'
        ],
        'response' => [
            'content' => [
                'activation_status'  => 'activated',
            ],
        ],
    ],

    'testMerchantDetailsFetchAccountServiceAccountDoesNotExist' => [
        'request' => [
            'method' => 'GET',
            'url' => '/account_service/accounts/{accountId}',
            'content' => [],
        ],
        'response' => [
            'content' =>  [
                "error" => [
                    "code" => "BAD_REQUEST_ERROR",
                    "description" => "The merchant id does not exist or invalid",
                    "source" => "NA",
                    "step" => "NA",
                    "reason" => "NA",
                    "metadata"=> [],
                    "field" => "id",
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_ID_DOES_NOT_EXIST,
        ],
     ],

    'testMerchantDetailsFetchAccountService' => [
        'request' => [
            'method' => 'GET',
            'url' => '/account_service/accounts/{accountId}',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'org_id' => '100000razorpay',
                ],
                'merchant_details' => [
                    'business_category' => 'financial_services',
                ],
                'stakeholders' => [
                    [
                        'name' => 'stakeholder name',
                        'percentage_ownership' => 90
                    ]
                ],
                'merchant_emails' => [
                    [
                        'type' => 'refund',
                    ]
                ],
                'merchant_documents' => [
                    [
                        'document_type' => 'address_proof_url',
                    ]
                ],
                'stakeholder_documents' => [
                    [
                        'document_type' => 'aadhar_front',
                    ]
                ],
                'merchant_website' => [
                    'refund_process_period'=> "3-5 days",
                    'admin_website_details'=> "{'website':'surkar.in'}"
                ],
                'merchant_business_detail' => [
                    "business_parent_category"=> "ABC",
                    "app_urls" => "{'app_url':'playstore.com/manthan/'}"
                ],
            ],
        ],
    ],

    'testFetchAccountServiceStakeholderDocumentNoStakeholderEntity' => [
        'request' => [
            'method' => 'GET',
            'url' => '/account_service/accounts/{accountId}',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'org_id' => '100000razorpay',
                ],
                'merchant_details' => [
                    'business_category' => 'financial_services',
                ],
                'stakeholders' => [],
                'merchant_emails' => [
                    [
                        'type' => 'refund',
                    ]
                ],
                'merchant_documents' => [
                    [
                        'document_type' => 'address_proof_url',
                    ],
                    [
                        'document_type' => 'aadhar_back',
                    ]
                ],
                'stakeholder_documents' => [
                    [
                        'document_type' => 'aadhar_front',
                    ],
                ],
            ],
        ],
    ],

    'testMerchantDetailsFetchAccountServiceNoStakeholder' => [
        'request' => [
            'method' => 'GET',
            'url' => '/account_service/accounts/{accountId}',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'org_id' => '100000razorpay',
                ],
                'merchant_details' => [
                    'business_category' => 'financial_services',
                ],
                'stakeholders' => [],
                'merchant_emails' => [
                    [
                        'type' => 'refund',
                    ]
                ],
                'merchant_documents' => [
                    [
                        'document_type' => 'address_proof_url',
                    ],
                    [
                        'document_type' => 'aadhar_front',
                    ],
                    [
                        'document_type' => 'aadhar_back',
                    ],
                ],
                'stakeholder_documents' => [],
            ],
        ],
    ],


    'testUpdatedAccountsFetchAccountService' => [
        'request' => [
            'method' => 'GET',
            'url' => '/account_service/updated_accounts',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'count' => 0,
                'account_ids' => [
                ]
            ]
        ],
    ],


    'testMerchantDetailsPatch' => [
        'request'  => [
            'content' => [
                'business_operation_address'               => 'Test address',
                'business_operation_state'                 => 'KA',
                'business_operation_city'                  => 'Bengaluru',
                'business_operation_pin'                   => '560030',
                'business_category'                        => 'financial_services',
                'business_subcategory'                     => 'lending',
                'international_activation_flow'            => 'whitelist',
                'estd_year'                                => '2020',
                'date_of_establishment'                    => '1992-12-01',
                'authorized_signatory_residential_address' => '12345rtyuk',
                'authorized_signatory_dob'                 => '1992-12-12',
                'platform'                                 => 'web',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_operation_address'               => 'Test address',
                'business_operation_state'                 => 'KA',
                'business_operation_city'                  => 'Bengaluru',
                'business_operation_pin'                   => '560030',
                'business_category'                        => 'financial_services',
                'business_subcategory'                     => 'lending',
                'international_activation_flow'            => 'whitelist',
                'estd_year'                                => '2020',
                'date_of_establishment'                    => '1992-12-01',
                'authorized_signatory_residential_address' => '12345rtyuk',
                'authorized_signatory_dob'                 => '1992-12-12',
                'platform'                                 => 'web',
            ],
        ],
    ],

    'testMerchantDetailsEditMobileNumber' => [
        'request'  => [
            'content' => [
                'contact_mobile'                            => '0179164389'
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'contact_mobile'                            => '+60179164389'
            ],
        ],
    ],

    'testMerchantDetailsEditMobileNumberWithPrefix' => [
        'request'  => [
            'content' => [
                'contact_mobile'                            => '+600179164389'
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'contact_mobile'                            => '+60179164389'
            ],
        ],
    ],

    'testMerchantDetailsEditMobileNumberIndia' => [
        'request'  => [
            'content' => [
                'contact_mobile'                            => '9876543210'
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'contact_mobile'                            => '+919876543210'
            ],
        ],
    ],

    'testSmartDashboardMerchantDetailsPatch' => [
        'request'  => [
            'content' => [
                'merchant_details|business_operation_address'                      => 'Test address',
                'merchant_details|business_operation_state'                        => 'KA',
                'merchant_details|business_operation_city'                         => 'Bengaluru',
                'merchant_details|business_operation_pin'                          => '560030',
                'merchant_details|merchant|website'                                => 'https://www.test.com',
                'merchant_details|merchant_business_detail|app_urls|playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                'merchant_business_detail|pg_use_case' => 'we have very good business use case, but we are in loss right now'
            ],
            'url'     => '/smart_dashboard/merchants/details',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testMerchantDetailsPatchShouldUpdateMethodsBasedOnCategory' => [
        'request' => [
            'raw' => json_encode([
                'business_category'      => 'tours_and_travel',
                'business_subcategory'   => 'travel_agency',
                'reset_methods'          => true,
            ]),
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ]
],
        'response' => [
            'content' => [
                'business_category'                        => 'tours_and_travel',
                'business_subcategory'                     => 'travel_agency',
            ],
        ],
    ],

    'testMerchantDetailsPatchShouldNotUpdateMethodsBasedOnCategoryIfResetMethodsIsFalse' => [
        'request' => [
            'raw' => json_encode([
                'business_category'      => 'tours_and_travel',
                'business_subcategory'   => 'travel_agency',
                'reset_methods'          => false,
            ]),
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
            'server' => [
                'CONTENT_TYPE'  => 'application/json',
            ]
],
        'response' => [
            'content' => [
                'business_category'                        => 'tours_and_travel',
                'business_subcategory'                     => 'travel_agency',
            ],
        ],
    ],

    'testMerchantDetailsPatchMerchantContextNotSet' => [
        'request'  => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
                'business_category'          => 'financial_services',
                'business_subcategory'       => 'lending',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_CONTEXT_NOT_SET,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_CONTEXT_NOT_SET,
        ],
    ],

    'testMerchantDetailsPatchInvalidBusinessSubcategory' => [
        'request'  => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
                'business_category'          => 'education',
                'business_subcategory'       => 'lending',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid business subcategory for business category: education',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantDetailsPatchInvalidInternationalActivtionFlow' => [
        'request'  => [
            'content' => [
                'international_activation_flow' => 'whatlist',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid activation flow: whatlist',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantDetailsPatchBusinessNamePresent' => [
        'request'  => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
                'business_category'          => 'education',
                'business_subcategory'       => 'schools',
                'business_name'              => 'Studio Bara',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
                'business_category'          => 'education',
                'business_subcategory'       => 'schools',
                'business_name'              => 'Studio Bara',
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantDetailsPatchValidStatusChange' => [
        'request'  => [
            'content' => [
                'bank_details_verification_status' => 'verified',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content'     => [
                'bank_details_verification_status' => 'verified',
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantDetailsPatchInvalidStatusChange' => [
        'request'   => [
            'content' => [
                'bank_details_verification_status' => 'failed',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'BAD_REQUEST_INVALID_BANK_DETAIL_VERIFICATION_STATUS_CHANGE',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantDetailsPatchNoBusinessCategorySubcategory' => [
        'request'  => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
            ],
        ],
    ],

    'testMerchantDetailsPatchBusinessModel' => [
        'request'  => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
                'business_category'          => 'others',
                'business_model'             => 'Acme corp',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'KA',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
                'business_model'             => 'Acme corp',
            ],
        ],
    ],

    'testMerchantUpdateWebsiteDetails' => [
        'request'  => [
            'content' => [
                'business_website' => 'https://www.example.com',
            ],
            'url'     => '/merchant/activation/update_website_details',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_website' => 'https://www.example.com',
                'has_key_access'   => true,
            ],
        ],
    ],

    'testAddMerchantActivationWebsiteDetailsWorkflowApprove' => [
        'request'  => [
            'content' => [
                'business_website' => 'https://www.example.com',
            ],
            'url'     => '/merchant/activation/update_website_details',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateMerchantContactWithContactAlreadyExistsFailure' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '1234567890',
                'new_contact_number' => '8722627189'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>  PublicErrorDescription::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' =>  ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN,
        ],
    ],

    'testUpdateMerchantContactMultipleOwnersExistWithContactNumberFail' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '1234567890',
                'new_contact_number' => '8722627189'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>  PublicErrorDescription::BAD_REQUEST_MULTI_OWNER_ACCOUNTS_ASSOCIATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' =>  ErrorCode::BAD_REQUEST_MULTI_OWNER_ACCOUNTS_ASSOCIATED,
        ],
    ],

    'testUpdateMerchantContactNoOwnerExistWithContactNumberFail' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '1234567890',
                'new_contact_number' => '8722627189'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>  PublicErrorDescription::BAD_REQUEST_NO_OWNER_ACCOUNTS_ASSOCIATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' =>  ErrorCode::BAD_REQUEST_NO_OWNER_ACCOUNTS_ASSOCIATED,
        ],
    ],

    'testUpdateMerchantContactWithWorkflowReject' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '1234567890',
                'new_contact_number' => '8722627189'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateMerchantContactWithWorkflow' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '1234567890',
                'new_contact_number' => '8722627189'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateMerchantContactWithSameNewNumberAndMerchantContactDetails' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '1234567890',
                'new_contact_number' => '8722627189'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantUpdateWebsiteDetailsIpv6' => [
        'request'  => [
            'content' => [
                'business_website' => 'https://cholasmartedisuat.chola.murugappa.com',
            ],
            'url'     => '/merchant/activation/update_website_details',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_website' => 'https://cholasmartedisuat.chola.murugappa.com',
                'has_key_access'   => true,
            ],
        ],
    ],

    'testCommentMerchant' => [
        'request' => [
            'content' => [
                'comment' => 'true'
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testPreventEditingBuisnessNameInMIQ' => [
        'request' => [
            'content' => [
                'business_name' => 'test 2',
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Business name cannot be changed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testMerchantUpdateBusinessDetails' => [
        'request'  => [
            'content' => [
                "website_details" => [
                    "about"=> "http://hello.com"
                ]
            ],
            'url'     => '/merchant/{id}/business/detail',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                "website_details"=> [
                    "about"=> "http://hello.com"
                ]
            ],
        ],
    ],
    'testMerchantUpdateMiqAndTestingHappy' => [
        'request'  => [
            'content' => [
                'miq_sharing_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                'testing_credentials_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
            ],
            'url'     => '/merchant/{id}/business/detail',
            'method'  => 'POST',
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'miq_sharing_date'=> strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                'testing_credentials_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
            ],
        ],
    ],
    'testMerchantUpdateMiqAndTestingUnHappyOrgFeature' => [
        'request'  => [
            'content' => [
                'miq_sharing_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
                'testing_credentials_date' => strtotime('yesterday midnight'. ' '. 'Asia/Kolkata'),
            ],
            'url'     => '/merchant/{id}/business/detail',
            'method'  => 'POST',
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'Access Denied',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED,
        ],
    ],
    'testMerchantUpdateMiqAndTestingUnHappyInvalidDates' => [
        'request'  => [
            'content' => [
                'miq_sharing_date' => strtotime('+3 day'. ' '. 'Asia/Kolkata'),
                'testing_credentials_date' => strtotime('tomorrow midnight'. ' '. 'Asia/Kolkata'),
            ],
            'url'     => '/merchant/{id}/business/detail',
            'method'  => 'POST',
            'convertContentToString' => false
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => 'BAD_REQUEST_ERROR',
                    'description' => 'BAD_REQUEST_INVALID_MIQ_SHARING_DATE',
                    'source' => 'business',
                    'step' =>  'payment_initiation',
                    'reason'=> 'input_validation_failed',
                    'metadata'=> []
                 ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],
    'testMerchantReviewer' => [
        'request' => [
            'content' => [
                'reviewer_id' => Org::SUPER_ADMIN_SIGNED
            ],
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'reviewer' => [
                    'id' => Org::SUPER_ADMIN_SIGNED
                ],
            ],
        ],
    ],

    'testCommentForLockedMerchant' => [
        'request' => [
            'content' => [
                'comment' => 'true'
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testCommentMerchantWithNoMerchantDetail' => [
        'request' => [
            'content' => [
                'comment' => 'true'
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUnlockMerchant' => [
        'request' => [
            'content' => [
                'locked' => 0
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'locked' => false,
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testUnlockMerchant2' => [
        'request' => [
            'content' => [
                'locked' => 0
            ],
            'url' => '/merchant/activation/lock',
            'method' => 'PUT'
        ],
        'response' => [
            'content' => [
                'locked' => false,
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testCreateMerchantDetailIfNotExist' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testZohoMerchantHeaders' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => ErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment failed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testMerchantDetailsFetch' => [
        'request' => [
            'url'       => '/merchants/details',
            'method'    => 'GET',
            'content'   => [],
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000002',
            ],
        ],
        'response' => [
            'content' => [
                'id'                        => '10000000000002',
                'entity'                    => 'merchant',
                'activated'                 => false,
                'live'                      => false,
                'methods'                   => [
                    'merchant_id'   => '10000000000002',
                    'amex'          => false,
                ],

                'convert_currency'          => null,
                'org_id'                    => \RZP\Tests\Functional\Fixtures\Entity\Org::RZP_ORG,
                'groups'                    => [],
                'admins'                    => [],
                'transaction_report_email'  => [],
                'tags'                      => [],
                'confirmed'                 => false,
                'logo_url'                  => null,
                'merchant_details'          => [
                    'contact_email'         => 'razorpay@razorpay.com',
                    'gstin'                 => null,
                    'p_gstin'               => null,
                    'activation_progress'   => 10,
                    'can_submit'            => false,
                    'steps_finished'        => [],
                    'activated'             => 0,
                    'verification'          => [
                        'status'                => 'disabled',
                        'disabled_reason'       => 'required_fields',
                        'activation_progress'   => 10,
                    ],
                ],
                'auto_capture_late_auth'    => false,
                'fee_bearer'                => 'platform',
                'fee_model'                 => 'prepaid',
                'international'             => true,
                'max_payment_amount'        => 50000000,
                'suspended_at'              => null,
            ],
        ],
    ],

    'testSmartDashboardMerchantDetailsFetch' => [
        'request' => [
            'url'       => '/smart_dashboard/merchants/details',
            'method'    => 'GET',
            'content'   => [],
            'server' => [
                'HTTP_' . \RZP\Http\RequestHeader::X_RAZORPAY_ACCOUNT => '10000000000155',
            ],
        ],
        'response' => [
            'content' => [],
        ],
    ],


    'testGetInternalMerchantMerchantDetailsFetch' => [
        'request' => [
            'url'       => '/internal/merchants/10000000000155',
            'method'    => 'GET',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'merchant' => [
                    'id' => '10000000000155',
                    'entity' => 'merchant',
                    'email' => 'razorpay@razorpay.com',
                    'website' => 'razorpay.com',
                ],
                'merchant_detail' => [
                    'business_type' => '1',
                    'transaction_volume' => 5,
                    'department'         => '6',
                    'contact_mobile'     => '+918722627189',
                    'contact_email'      => 'razorpay@razorpay.com',
                    'min_aov'            => 94,
                    'max_aov'            => 98,
                    'playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy',
                    'appstore_url' => 'https://play.google.com/store/apps/details?id=com.dummy123123',
                    'gstin'              => 'AAAA123456789A',
                    'authorized_signatory_residential_address' => 'test',
                    'authorized_signatory_dob' => '2022-03-12',
                    'estd_year' => 2022
                ],
                'merchant_document' => [
                    'promoter_address_url' => [
                        [
                            'file_store_id' => '123123',
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testExternalGetMerchantCompositeDetails' => [
        'request' => [
            'url'       => '/external_org/merchants/composite_details/',
            'method'    => 'GET',
            'content'   => [],
        ],
        'response' => [
            'content' => [
                'terminals' => [
                    [
                        'terminal_id'               => 'term_10000000000002',
                        'gateway_terminal_id'       => 'nodal account axis_migs',
                        'gateway_merchant_id'       => 'razorpay axis_migs',
                    ]
                ],
               'merchant' => [
                   'id'        => '100000razorpay',
                   'name'      => 'TestMerchant',
                   'website'   => 'http://goyette.net/',
                   'category'  => '1100',
               ],
            ],
        ],
    ],

    'testGetPreSignupDetails' => [
        'request' => [
            'content' => [],
            'url'     => '/pre_signup',
            'method'  => 'GET',
        ],
        'response' => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '5',
                'department'         => '6',
                'contact_mobile'     => '+918722627189',
                'contact_email'     => 'razorpay@razorpay.com'
            ],
        ],
    ],

    'testPutPreSignupDetails' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
                'contact_email'     => null,
            ],
        ],
    ],

    'testPutPreSignupDetailsForNeostone' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testPutPreSignupDetailsInXForUnregisteredBusiness' => [
        'request' => [
            'content' => [
                'business_name' => 'test',
                'business_type' => '11'
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT'
        ],
        'response' => [
            'content' => [
                'business_name'      => 'test',
                'business_type'      => '11',
                'transaction_volume' => null,
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testVaCreationTestModeInPreSignup' => [
        'request'  => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => ' Kill Bill Pandey ',
                'contact_mobile'     => '9087654321',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => ' Kill Bill Pandey ',
                'contact_mobile'     => '+919087654321',
                'role'               => null,
            ],
        ],
    ],

    'testBeneficiaryNameInVirtualBankingAccounts' => [
        'request'  => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => 'Razorpay',
                'contact_mobile'     => '9087654321',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => 'Razorpay',
                'contact_mobile'     => '+919087654321',
                'role'               => null,
            ],
        ],
    ],

    'testVaNotCreatedForBusinessBankingDisabledInTestModePreSignup' => [
        'request'  => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => 'Razorpay',
                'contact_mobile'     => '9087654321',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => 'Razorpay',
                'contact_mobile'     => '+919087654321',
                'role'               => null,
            ],
        ],
    ],

    'testVaNotCreatedInTestModeWhenMockedPreSignup' => [
        'request'  => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => 'Razorpay',
                'contact_mobile'     => '9087654321',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '1',
                'transaction_volume' => '1',
                'contact_name'       => 'Razorpay',
                'contact_mobile'     => '+919087654321',
                'role'               => null,
            ],
        ],
    ],

    'testPutPreSignupDetailsForUnregisteredBusiness' => [
        'request'  => [
            'content' => [
                'business_type'  => '11',
                'contact_name'   => 'I am untegistered',
                'contact_mobile' => '8722627189',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'  => '11',
                'contact_name'   => 'I am untegistered',
                'contact_mobile' => '+918722627189',
            ],
        ],
    ],

    'testPutPreSignupDetailsWithCouponCode' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'coupon_code'   => 'RANDOM',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testPutPreSignupDetailsWithInvalidCouponCode' => [
        'request'   => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'coupon_code'   => 'RANDOM',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_COUPON_CODE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE,
        ],
    ],

    'testPutPreSignupDetailsWithSystemCouponCode' => [
        'request'   => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'coupon_code'   => 'OFFERMTU',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INVALID_COUPON_CODE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_COUPON_CODE,
        ],
    ],

    'testPutPreSignupDetailsWithPartnerCouponCodeForBanking' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'coupon_code'   => 'RANDOM',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testFetchMerchantConsents' => [
        'request' => [
            'url'    => '/merchant/consents',
            'method' => 'GET',
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ]
    ],

    'testBulkAssignReviewer' => [
        'request' => [
            'content' => [
                'reviewer_id' => Org::SUPER_ADMIN_SIGNED,
                'merchants'   => [
                    '10000000000000'
                ],
            ],
            'url'     => '/merchant/activation/bulk_assign_reviewer',
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

    'testMerchantsMtuUpdateSuccess' => [
        'request' => [
            'content' => [
                'merchants'   => [
                    '10000000000000'
                ],
                'live_transaction_done' => '1',
            ],
            'url'       => '/merchant_mtu_update',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'success'       => 1,
                'failed'        => 0,
                'failedItems'   => [],
            ],
        ],
    ],

    'testMerchantsMtuUpdateIdFailure' => [
        'request' => [
            'content' => [
                'merchants'   => [
                    ''
                ],
                'live_transaction_done' => '1',
            ],
            'url'       => '/merchant_mtu_update',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                'success'       => 0,
                'failed'        => 1,
                'failedItems'   => [
                    [
                        'merchant_id' => '',
                        'error' => 'The id provided does not exist'
                    ]
                ],
            ],
        ],
    ],

    'testMerchantsMtuUpdateLiveTransactionFailure' => [
        'request' => [
            'content' => [
                'merchants' => ["10000000000000"],
                'live_transaction_done' => '3',
            ],
            'url' => '/merchant_mtu_update',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected live transaction done is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetRequestDocumentList' => [
        'request' => [
            'method' => 'GET',
            'url' => '/merchant/activation/whatsapp_notification_document_list'
        ],
        'response' => [
            'content' => [
                'count' => 8,
                'items' => [
                    'Partnership deed in pdf format',
                    'CA professional certificate',
                    'Clarity on the business model and products/services offered by you',
                    'Certificate of incorporation',
                    'Website must be live with all the sections updated - About us, Contact us, Refund/Cancellation policy, Terms and Conditions, Privacy policy',
                    'Business Proof: Kindly provide us all the pages of the Trust deed in pdf format',
                    'Reseller agreement or bulk purchase invoice',
                    'Other'
                ],
            ],
        ],
    ],

    'testSendRequestDocumentWhatsappNotification' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchant/activation/10000000000000/send_whatsapp_notification',
            'content' => [
                'ticket_id' => '123',
                'documents' => [
                    'doc_1',
                    'doc_2',
                    'doc_3',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testSendRequestDocumentWhatsappNotificationFailure' => [
        'request' => [
            'method' => 'POST',
            'url' => '/merchant/activation/10000000000000/send_whatsapp_notification',
            'content' => [
                'ticket_id' => '123',
                'documents' => [
                    'doc_1',
                    'doc_2',
                    'doc_3',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_NO_TICKETS_FOUND_FOR_CUSTOMER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NO_TICKETS_FOUND_FOR_CUSTOMER,
        ],
    ],

    'testBulkEditMerchantAttributes' => [
        'request' => [
            'method' => 'POST',
            'url'    => '/merchants/bulk/attributes',
        ],
        'response' => [
            'content' => [
                'total'  => 1,
                'failed' => 0,
            ],
        ],
    ],

    'testUpdateCriticalFieldsPostActivation' => [
        'request'  => [
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'lending',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_DETAIL_CANNOT_BE_UPDATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateCriticalFieldsPostActivationEasyOnboarding' => [
        'request'  => [
            'content' => [
                'business_category'    => 'financial_services',
                'business_subcategory' => 'lending',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'   => false,
            ],
        ],
    ],

    'testUpdateNonCriticalFieldsPostActivation' => [
        'request'  => [
            'content' => [
                'promoter_pan_name' => 'John Doe',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'promoter_pan_name' => 'John Doe',
            ],
            'status_code' => 200,
        ],
    ],

    'testCategoryDetailsSetOnSubCategoryChange' => [
        'request'  => [
            'content' => [
                'business_subcategory' => 'mutual_fund',
                'business_category'    => 'financial_services',
                'business_name'        => 'test'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'   => false,
            ],
        ],
    ],

    'testCategoryDetailsSetForOthersCategory' => [
        'request'  => [
            'content' => [
                'business_category'    => 'others',
                'business_subcategory' => null,
                'business_name'        => 'test'
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'   => false,
            ],
        ],
    ],

    'testSupportedActivationFlow' => [
        'request'  => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'   => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testUnsupportedActivationFlow' => [
        'request'   => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC0000002',
                'submit'                    => 1,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNSUPPORTED_BUSINESS_SUBCATEGORY,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNSUPPORTED_BUSINESS_SUBCATEGORY,
        ],
    ],

    'testBlacklistActivationFlowEasyOnboarding' => [
        'request'   => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC0000002',
                'submit'                    => 1,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [
                'verification' => [
                    'status'              => 'pending',
                    'activation_progress' => 80,
                ],
                'can_submit'   => true,
            ],
            'status_code' => 200,
        ],
    ],

    'testBlacklistActivationFlowCanSubmit' => [
        'request'   => [
            'content' => [
                'bank_branch_ifsc'          => 'ICIC0000002',
                'submit'                    => 1,
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'   => false,
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantDetailsPatchCategoryAutoPopulation' => [
        'request'  => [
            'content' => [
                "business_category"    => "financial_services",
                "business_subcategory" => "mutual_fund",
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                "business_category"    => "financial_services",
                "business_subcategory" => "mutual_fund",
            ],
        ],
    ],

    'testWebsiteDetailsShouldBeInSync' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_name'    => 'facebook',
                'business_website' => 'https://example.com',
            ],
        ],
        'response' => [
            'content' => [
                'business_name'    => 'facebook',
                'business_website' => 'https://example.com',
            ],
        ],
    ],

    'testFileUploadSyncInDetailAndDocumentTable' => [
        'request'  => [
            'method' => 'post',
            'url'    => '/merchant/activation/upload',
        ],
        'response' => [
            'content' => [

            ]
        ],
    ],

    'testUpdateKYCClarificationReason' => [
        'request'  => [
            'content' => [
                'kyc_clarification_reasons' => [
                    'clarification_reasons' => [
                        'field1' => [[
                            'reason_type' => 'custom',
                            'field_value' => 'adnakdad',
                            'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                        ]],
                        'field3' => [[
                            'reason_type' => 'predefined',
                            'field_value' => 'adnakdad',
                            'reason_code' => 'provide_poc',
                        ]],
                    ],
                    'additional_details'    => [
                        'field3'               => [[
                            'reason_type' => 'custom',
                            'field_type'  => 'document',
                            'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                        ]],
                        'business_description' => [[
                            'reason_type' => 'predefined',
                            'field_type'  => 'text',
                            'reason_code' => 'provide_poc',
                        ]],
                    ],
                ],
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'kyc_clarification_reasons' => [
                    'clarification_reasons' => [
                        'field1' => [[
                            'reason_type' => 'custom',
                            'field_value' => 'adnakdad',
                            'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                        ]],
                        'field3' => [[
                            'reason_type' => 'predefined',
                            'field_value' => 'adnakdad',
                            'reason_code' => 'provide_poc',
                        ]],
                    ],
                    'additional_details'    => [
                        'field3'               => [[
                            'reason_type' => 'custom',
                            'field_type'  => 'document',
                            'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                        ],],
                        'business_description' => [[
                            'reason_type' => 'predefined',
                            'field_type'  => 'text',
                            'reason_code' => 'provide_poc',
                        ]],
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateKYCClarificationReasonWithFailure' => [
        'request'   => [
            'content' => [
                'kyc_clarification_reasons' => [
                    'clarification_reasons' => [
                        'field3' => [[
                                         'reason_type' => 'alndalnd',
                                         'field_value' => 'adnakdad',
                                         'reason_code' => 'provide_poc',
                                     ]],
                    ]
                ],
            ],
            'method'  => 'PUT',
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

    'testUpdateKycAdditionalDetails' => [
        'request'  => [
            'content' => [
                'kyc_additional_details' => [
                    'business_description' => [
                        'field_value' => 'xyz',
                    ],
                ],
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateKycAdditionalDetailsWithFailure' => [
        'request'   => [
            'content' => [
                'kyc_additional_details' => [
                    'business_description' => [
                        'field_value' => 'xyz',
                    ],
                ],
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not required additional field :business_description',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateKycAdditionalDetailsWithInvalidField' => [
        'request' => [
            'content' => [
                'kyc_additional_details' => [
                    'text_field_xyz' => [
                        'value' => 'xyz',
                    ],
                ],
            ],
            'url' => '/merchant/activation',
            'method' => 'POST',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not required additional field :text_field_xyz',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateKycAdditionalDetailsData' => [
        'kyc_clarification_reasons' => [
            'clarification_reasons' => [
                'field1' => [[
                    'reason_type' => 'custom',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]],
                'field3' => [[
                    'reason_type' => 'predefined',
                    'field_value' => 'adnakdad',
                    'reason_code' => 'provide_poc',
                ]],
            ],
            'additional_details'    => [
                'field3'               => [[
                    'reason_type' => 'custom',
                    'field_type'  => 'document',
                    'reason_code' => 'Lorem ipsum dolor sit amet consectetuer',
                ]],
                'business_description' => [[
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]],
            ],
        ],
    ],

    'testAdditionalWebsite' => [
        'request'  => [
            'content' => [
                'additional_website' => 'https://example.com',
            ],
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'additional_websites' => [
                    'https://example.com',
                ]
            ],
            'status_code' => 200,
        ],
    ],

    'testWebsiteNotLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_website' => 'http://razorpays.com/',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Enter a live/operational URL. You can enter it later if you don't have a live URL now"
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testWebsiteNotLiveSplitzKqu' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_website' => 'http://razorpays.com/',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Enter a live/operational URL. You can enter it later if you don't have a live URL now"
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPopularWebsite' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_website' => 'http://google.com/',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testPopularWebsiteSplitzKqu' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_website' => 'http://google.com/',
            ],
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testWebsiteNotLiveSplitzOff' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_website' => 'http://razorpays.com/',
            ],
        ],
        'response' => [
            'content' => [
                'business_website' => 'http://razorpays.com/',
            ],
            'status_code' => 200,
        ],
    ],

    'testCINSignatorySuccessExperimentLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'U67190TN2014PTC096971'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'U67190TN2014PTC096971',
            ],
            'status_code' => 200,
        ],
    ],

    'testCINSignatoryFailureExperimentLiveAsync' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'U67190TN2014PTC096972'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'U67190TN2014PTC096972',
            ],
            'status_code' => 200,
        ],
    ],

    'testCINSignatorySuccessExperimentLiveAsync' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'U67190TN2014PTC096971'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'U67190TN2014PTC096971',
            ],
            'status_code' => 200,
        ],
    ],

    'testCINSignatorySuccessExperimentNotLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'U67190TN2014PTC096971'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'U67190TN2014PTC096971',
            ],
            'status_code' => 200,
        ],
    ],

    'testCINSignatoryFailureExperimentLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'U67190TN2014PTC096972'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'U67190TN2014PTC096972',
            ],
            'status_code' => 200,
        ],
    ],

    'testLLPINSignatorySuccessExperimentLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'AAA-0001'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'AAA-0001',
            ],
            'status_code' => 200,
        ],
    ],

    'testLLPINSignatorySuccessExperimentNotLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'AAA-0001'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'AAA-0001',
            ],
            'status_code' => 200,
        ],
    ],

    'testLLPINSignatoryFailureExperimentLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'company_cin'=>'AAA-0002'
            ],
        ],
        'response' => [
            'content' => [
                'company_cin'=>'AAA-0002',
            ],
            'status_code' => 200,
        ],
    ],

    'testGSTINSignatorySuccessExperimentLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'gstin'=>'01AADCB1234M1ZX'
            ],
        ],
        'response' => [
            'content' => [
                'gstin'=>'01AADCB1234M1ZX',
            ],
            'status_code' => 200,
        ],
    ],

    'testGSTINSignatorySuccessExperimentNotLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'gstin'=>'01AADCB1234M1ZX'
            ],
        ],
        'response' => [
            'content' => [
                'gstin'=>'01AADCB1234M1ZX',
            ],
            'status_code' => 200,
        ],
    ],

    'testGSTINSignatoryFailureExperimentLive' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
               'gstin'=>'03AADCB1234M1ZX'
            ],
        ],
        'response' => [
            'content' => [
                'gstin'=>'03AADCB1234M1ZX',
            ],
            'status_code' => 200,
        ],
    ],

    'testWebsiteNotLiveSplitzPilot' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_website' => 'http://razorpays.com/',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Enter a live/operational URL. You can enter it later if you don't have a live URL now"
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testAdditionalWebsiteMaxLimitFailure' => [
        'request'   => [
            'content' => [
                'additional_website' => 'https://example.com',
            ],
            'method'  => 'PUT',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The additional websites may not have more than 15 items.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testDeleteAdditionalWebsites' => [
        'request'  => [
            'content' => [
                'additional_websites' => [
                    'https://example.com',
                    'https://www.website1.com',
                    'https://www.website4.com',
                ],
            ],
            'method'  => 'delete',
        ],
        'response' => [
            'content'     => [
                'additional_websites' => [
                    'https://www.website2.com',
                    'https://www.website3.com',
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testPutPreSignUpDetailsWithReferralCode' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code'   => 'teslacomikejzc',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPutPreSignupDetailsWithInvalidCapitalReferralCode' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code' => 'invalid'
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
                'business_type' => '2',
                'department' => '7',
            ],
        ],
    ],

    'testPutPreSignupDetailsWithCapitalReferralCode' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code'   => 'teslacomikejzc',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPutPreSignUpDetailsWithBankingReferralCodeInX' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code'   => 'teslacomikejzc',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPutPreSignUpDetailsWithPrimaryReferralCodeInX' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code'   => 'teslacomikejzc',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
            'server' => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPutPreSignUpDetailsWithReferralCodeForAggregator' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code'   => 'teslacomikejzc',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPutPreSignUpDetailsWithPrimaryReferralCodeInXForAggregator' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code'   => 'teslacomikejzc',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testPutPreSignUpDetailsWithInvalidReferralCode' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'referral_code'   => 'teslacomikejzc',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetMerchantDetailsWithBalanceConfigs' => [
        'request'  => [
            'url'    => '/merchants/details',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'confirmed' => false,
                'balance_configs'    => [
                    'items' => [
                            '0' => [
                                'id'                            =>  '100ab000ab00ab',
                                'balance_id'                    =>  '100abc000abc00',
                                'type'                          =>  'banking',
                                'negative_transaction_flows'   =>  ['payout'],
                                'negative_limit_auto'          =>  5000000,
                                'negative_limit_manual'        =>  5000000
                            ],
                            '1' => [
                                'id'                            =>  '100yz000yz00yz',
                                'balance_id'                    =>  '100def000def00',
                                'type'                          =>  'primary',
                                'negative_transaction_flows'   =>  ['refund'],
                                'negative_limit_auto'           =>  5000000,
                                'negative_limit_manual'         =>  5000000
                            ],
                    ]
                ],
                'is_inheritance_parent' =>  false,
            ],
        ],
    ],

    'testGetAccountingIntegrationMerchantDetails' => [
        'request'  => [
            'url'    => '/accounting-integration/merchant/details',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'activation_status' => 'activated'
            ],
        ],
    ],

    'testStoreCaseInsensitiveDomain' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_name'    => 'facebook',
                'business_website' => 'https://EXAMPLE.CoM',
            ],
        ],
        'response' => [
            'content' => [
                'business_website' => 'https://EXAMPLE.CoM',
            ],
        ],
    ],

    'testGetMerchantDetailsRegisteredBusinessWithSelectiveRequiredFields' => [
        'request'  => [
            'url'    => '/merchant/activation',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                    'required_fields' => [
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',
                        'contact_mobile',
                        'contact_name',
                        'promoter_pan_name',
                        'business_dba',
                        'business_name',
                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'promoter_address_url',
                        'business_pan_url',
                        'business_proof_url',
                        'amfi_certificate',
                    ],
                    'optional_fields' => [
                    ],
                ],
                'can_submit'   => false,
            ],
        ],
    ],
    'testGetMerchantDetailsRegisteredBusinessWithOptionalFields' => [
        'request'  => [
            'url'    => '/merchant/activation',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                    'required_fields' => [
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',
                        'contact_mobile',
                        'contact_name',
                        'promoter_pan_name',
                        'business_dba',
                        'business_name',
                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'promoter_address_url',
                        'business_pan_url',
                        'business_proof_url',
                    ],
                    'optional_fields' => [
                        'iata_certificate',
                        'sla_iata_certificate'
                    ],
                ],
                'can_submit'   => false,
            ],
        ],
    ],

    'testGetMerchantDetailsRegisteredBusinessNgo' => [
        'request'  => [
            'url'    => '/merchant/activation',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                    'required_fields' => [
                        'bank_account_name',
                        'bank_account_number',
                        'bank_branch_ifsc',
                        'business_registered_address',
                        'business_registered_city',
                        'business_registered_pin',
                        'business_registered_state',
                        'contact_mobile',
                        'contact_name',
                        'promoter_pan_name',
                        'business_dba',
                        'business_name',
                        'business_operation_address',
                        'business_operation_city',
                        'business_operation_pin',
                        'business_operation_state',
                        'form_12a_url',
                        'form_80g_url',
                        'business_pan_url',
                        'business_proof_url',
                        'promoter_address_url',
                    ],
                    'optional_fields' => [
                        'affiliation_certificate'
                    ],
                ],
                'can_submit'   => false,
            ],
        ],
    ],

    'testCompanyPanVerificationBusinessNameUpdate' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_name'    => 'Test123',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testCompanyPanVerificationCompanyPanUpdate' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'company_pan'    => 'ABCAD1234J',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testPromoterPanVerificationPromoterPanNameUpdate' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'promoter_pan_name' => 'Test123',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'testPromoterPanVerificationPromoterPanUpdate' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'promoter_pan' => 'ABCPD1234J',
            ],
        ],
        'response' => [
            'content' => [

            ],
        ],
    ],

    'gstinVerification' => [
        'request'  => [
            'content' => [
                'gstin' => '07AADCB2230M1ZV',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'gstin'        => '07AADCB2230M1ZV',
                'verification' => [
                    'status'          => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit'   => false,
            ],
        ],
    ],

    'testGSTINVerificationFuzzyMatchFailureOnBusinessName' => [
        'request' => [
            'content' => [
                'gstin' => '07AADCB2230M1ZV',
                'business_name' => 'random business'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'gstin' => '07AADCB2230M1ZV',
                'business_name' => 'random business',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testRequestOriginInHubspotPreSignupDetails' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testRequestOriginInHubspotPreSignupDetailsForPrimary' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'role'               => null,
            ],
        ],
    ],

    'testPutPresignupDetailsWithEmail' => [
        'request' => [
            'content' => [
                'business_type'     => '2',
                'department'        => '7',
                'contact_email'     => "yolomail@yolo.com",
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'business_type'      => '2',
                'transaction_volume' => null,
                'department'         => '7',
                'contact_mobile'     => null,
                'contact_email'      => "yolomail@yolo.com",
                'role'               => null,
            ],
        ],
    ],

    'testPutPresignupDetailsWithEmailSignupViaEmail' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
                'contact_email'         => 'yolo123@yolo.com'
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::PRE_SIGNUP_EMAIL_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPutPresignupDetailsWithMobileSignupViaMobile' => [
        'request' => [
            'content' => [
                'business_type'     => '2',
                'department'        => '7',
                'contact_mobile'    => '9998880000'
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::PRE_SIGNUP_CONTACT_MOBILE_NOT_ALLOWED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPutPresignupDetailsWithEmailExists' => [
        'request' => [
            'content' => [
                'business_type'     => '2',
                'department'        => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact email has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPutPresignupDetailsWithContactMobileExistsUniquenessExperimentOn' => [
        'request' => [
            'content' => [
                'business_type' => '2',
                'department'    => '7',
            ],
            'url'     => '/pre_signup',
            'method'  => 'PUT',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact mobile has already been taken.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetBusinessDetailsWithEmptyString' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/business_details',
            'content' => [
                'search_string' => ''
            ]
        ],
        'response' => [
            'status_code'   => 200,
            'content'   =>[]
        ]
    ],

    'testSaveMerchantDetailsForActivationValidGSTINcheck' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'gstin' => '29AAGCR4375J1ZU',
                'business_type' => '1'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The GSTIN entered does not match your business information. Check details and try again.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testSaveMerchantDetailsForActivationNullGstinCheck' => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/merchant/activation',
            'content' => [
                'business_type' => '1'
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetBusinessDetails' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchant/activation/business_details',
            'content' => [
                'search_string' => 'comp'
            ]
        ],
        'response' => [
            'content'     =>
                [
                    [
                        "group_name" => "Ecommerce",
                        "group_value" => "ecommerce",
                        "matches"    => [
                            [
                                "subcategory_value" => "computer_software_stores",
                                "subcategory_name"  => "Computer Software Stores",
                                "tags"              => [
                                    "Computer"
                                ]
                            ],
                            [
                                "subcategory_value" => "computers_peripheral_equipment_software",
                                "subcategory_name"  => "Computers, Computer Peripheral Equipment, Software",
                                "tags"              => [
                                    "Computers",
                                    "Computer"
                                ]
                            ]
                        ]
                    ],
                    [
                        "group_name" => "Services",
                        "group_value"=> "services",
                        "matches"    => [
                            [
                                "subcategory_value" => "internet_service_providers",
                                "subcategory_name"  => "Computer Network/Information Services",
                                "tags"              => [
                                    "Computer"
                                ]
                            ]
                        ]
                    ],
                    [
                        "group_name" => "Computer Programming/Data Processing",
                        "group_value"=> "computer_programming_data_processing",
                        "matches"    => [
                            [
                                "subcategory_value" => "computer_programming_data_processing",
                                "subcategory_name"  => "Computer Programming/Data Processing",
                                "tags"              => [
                                    "Computer"
                                ]
                            ]
                        ]
                    ],
                    [
                        "group_name" => "Housing and Real Estate",
                        "group_value"=> "housing",
                        "matches"    => [
                            [
                                "subcategory_value" => "facility_management",
                                "subcategory_name"  => "Facility Management Company",
                                "tags"              => [
                                    "Company"
                                ]
                            ]
                        ]
                    ],
                    [
                        "group_name" => "IT and Software",
                        "group_value" => "it_and_software",
                        "matches"    => [
                            [
                                "subcategory_value" => "technical_support",
                                "subcategory_name"  => "Technical Support",
                                "tags"              => [
                                    "Computer"
                                ]
                            ]
                        ]
                    ],
                    [
                        "group_name" => "Tours and Travel",
                        "group_value" => "tours_and_travel",
                        "matches"    => [
                            [
                                "subcategory_value" => "aviation",
                                "subcategory_name"  => "Aviation",
                                "tags"              => [
                                    "Compania"
                                ]
                            ],
                            [
                                "subcategory_value" => "accommodation",
                                "subcategory_name"  => "Lodging and Accommodation",
                                "tags"              => [
                                    "Compri"
                                ]
                            ]
                        ]
                    ]
                ],
            'status_code' => 200,
        ],
    ],

    'testGetMerchantDetailsShopEstbVerifiableZone' => [
        'request'  => [
            'url'    => '/merchant/activation',
            'method' => 'GET'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'getSelfServeGetStatus'   => [
        'request' => [
                'url'       => '/merchant/gstin_self_serve',
                'method'    => 'GET',
        ],
        'response' => [
            'content'   => [
                'status'           => 'not_started',
                'rejection_reason' => null
            ],
        ],
    ],

    'testUpdateGstinSelfServe'      => [
        'request'   => [
            'url'       => '/merchant/gstin_self_serve',
            'method'    => 'POST',
            'content'   => [
                'gstin'                       => '18AABCU9603R1ZM',
            ],
        ],
        'response'  => [
            'content'   => [

            ],
        ],
    ],

    'testUpdateGstinSelfServeInvalidUserRole'      => [
        'request'   => [
            'url'       => '/merchant/gstin_self_serve',
            'method'    => 'POST',
            'content'   => [
                'gstin'                       => '18AABCU9603R1ZM',
            ],
        ],
        'response'  => [
            'content'   => [
                'error' => [
                    'code'              => 'BAD_REQUEST_ERROR',
                    'description'       => 'Authentication failed',
                ]
            ],
            'status_code' => 400,
        ],
    ],

    //
    // content is being set dynamically in test cases itself
    //
    'saveMerchantDetailsFields' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'raiseWorkflowMakerRequestToSaveBusinessWebsiteWithTestCredentials' => [
        'request'  => [
            'content' => [
                'business_website_main_page'       => 'https://www.example.com',
                'business_website_about_us'        => 'https://www.example.com/about_us',
                'business_website_contact_us'      => 'https://www.example.com/contact_us',
                'business_website_pricing_details' => 'https://www.example.com/pricing_details',
                'business_website_privacy_policy'  => 'https://www.example.com/privacy_policy',
                'business_website_refund_policy'   => 'https://www.example.com/refund_policy',
                'business_website_tnc'             => 'https://www.example.com/website_tnc',
                'business_website_username'        => 'test-user',
                'business_website_password'        => 'test-user-password'
            ],
            'url'     => '/merchant/save_business_website/website',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'raiseWorkflowMakerRequestToSaveBusinessWebsiteWithoutTestCredentials' => [
        'request'  => [
            'content' => [
                'business_website_main_page'       => 'https://www.example.com',
                'business_website_about_us'        => 'https://www.example.com/about_us',
                'business_website_contact_us'      => 'https://www.example.com/contact_us',
                'business_website_pricing_details' => 'https://www.example.com/pricing_details',
                'business_website_privacy_policy'  => 'https://www.example.com/privacy_policy',
                'business_website_refund_policy'   => 'https://www.example.com/refund_policy',
                'business_website_tnc'             => 'https://www.example.com/website_tnc',
            ],
            'url'     => '/merchant/save_business_website/website',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testGstinSelfServeStatus' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/gstin_update_self_serve/details',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
                'workflow_exists'          => true,
                'workflow_status'          => 'rejected',
                'rejection_reason_message' => 'Test body'
            ],
            'status_code' => 200,
        ],
    ],

    'testBusinessWebsiteOpenWorkflowStatus' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/additional_website/details',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
                'workflow_exists' => true,
                'workflow_status' => 'open'
            ],
            'status_code' => 200,
        ],
    ],

    'testGetMerchantWorkflowDetailsByInternalAuth' => [
        'request'  => [
            'content' => [
                "workflow_type" => [
                    "additional_website",
                ]
            ],
            'method'  => 'GET',
        ],
        'response' => [
            'content'     => [
                [
                    "workflow_name" => "additional_website",
                    "workflow_details" => [
                        "workflow_exists" => true,
                        "workflow_status" => "open",
                        "needs_clarification" => null,
                        "permission" => "update_merchant_website",
                        "request_under_validation" => false,
                    ]
                ],
            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateBusinessWebsiteAppRoleFail' => [
        'request'  => [
            'content' => [
                'business_website_main_page'       => 'https://www.example.com',
                'business_website_about_us'        => 'https://www.example.com/about_us',
                'business_website_contact_us'      => 'https://www.example.com/contact_us',
                'business_website_pricing_details' => 'https://www.example.com/pricing_details',
                'business_website_privacy_policy'  => 'https://www.example.com/privacy_policy',
                'business_website_refund_policy'   => 'https://www.example.com/refund_policy',
                'business_website_tnc'             => 'https://www.example.com/website_tnc'
            ],
            'url'     => '/merchant/save_business_website/website',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testBusinessWebsiteEncryption' => [
        'request'  => [
            'content' => [],
            'url'     => '/merchant/{actionId}/decrypt_website_comment',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
               'decrypted_info' => " website_username : test-user,       website_username's_password : test-user-password,"
            ],
            'status_code' => 200,
        ],
    ],

    'testBusinessWebsiteEncryptionCommentNotFound' => [
        'request'  => [
            'content' => [],
            'url'     => '/merchant/{actionId}/decrypt_website_comment',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ENCRYPTED_COMMENT_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ENCRYPTED_COMMENT_NOT_FOUND,
        ],
    ],

    'testRejectionReasonMerchantNotificationForWebsiteUpdateSelfServe' => [
        'request'  => [
            'content' => [
            ],
            'url'     =>  '/merchant/business_website_status',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
                'workflow_exists'          => true,
                'workflow_status'          => 'rejected',
                'rejection_reason_message' => 'Test body'
            ],
            'status_code' => 200,
        ],
    ],

    'testAddAdditionalWebsiteSelfServeWorkflowApprove' => [
        'request'  => [
            'content' => [
                'additional_website_main_page'          => 'https://www.example.com',
                'additional_website_about_us'           => 'https://www.example.com/about_us',
                'additional_website_contact_us'         => 'https://www.example.com/contact_us',
                'additional_website_pricing_details'    => 'https://www.example.com/pricing_details',
                'additional_website_privacy_policy'     => 'https://www.example.com/privacy_policy',
                'additional_website_refund_policy'      => 'https://www.example.com/refund_policy',
                'additional_website_tnc'                => 'https://www.example.com/website_tnc',
                'additional_website_test_username'      => 'username',
                'additional_website_test_password'      => 'password',
                'additional_website_reason'             => 'comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason'
            ],
            'url'     => '/merchant/additional_website/website',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testAddAdditionalWebsiteSelfServeMerchantActivationFailure' => [
        'request'   => [
            'content' => [
                'additional_website_main_page'          => 'https://www.example.com',
                'additional_website_about_us'           => 'https://www.example.com/about_us',
                'additional_website_contact_us'         => 'https://www.example.com/contact_us',
                'additional_website_pricing_details'    => 'https://www.example.com/pricing_details',
                'additional_website_privacy_policy'     => 'https://www.example.com/privacy_policy',
                'additional_website_refund_policy'      => 'https://www.example.com/refund_policy',
                'additional_website_tnc'                => 'https://www.example.com/website_tnc',
                'additional_website_test_username'      => 'username',
                'additional_website_test_password'      => 'password',
                'additional_website_reason'             => 'comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason'
            ],
            'url'     => '/merchant/additional_website/website',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>  PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' =>  ErrorCode::BAD_REQUEST_MERCHANT_NOT_ACTIVATED,
        ],
    ],

    'testAddAdditionalWebsiteSelfServeRoleFailure' => [
        'request'   => [
            'content' => [
                'additional_website_main_page'          => 'https://www.example.com',
                'additional_website_about_us'           => 'https://www.example.com/about_us',
                'additional_website_contact_us'         => 'https://www.example.com/contact_us',
                'additional_website_pricing_details'    => 'https://www.example.com/pricing_details',
                'additional_website_privacy_policy'     => 'https://www.example.com/privacy_policy',
                'additional_website_refund_policy'      => 'https://www.example.com/refund_policy',
                'additional_website_tnc'                => 'https://www.example.com/website_tnc',
                'additional_website_test_username'      => 'username',
                'additional_website_test_password'      => 'password',
                'additional_website_reason'             => 'comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason'
            ],
            'url'     => '/merchant/additional_website/website',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Authentication failed',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testAddAdditionalApp' => [
        'request'  => [
            'content' => [
                'additional_app_url'              => 'https://play.google.com/store/apps/details?id=com.abc.app.test',
                'additional_app_test_username'    => 'username',
                'additional_app_test_password'    => 'password',
                'additional_app_reason'           => 'comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason comment for reason'
            ],
            'url'     => '/merchant/additional_website/app',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testRejectionReasonMerchantNotificationForWebsiteAddSelfServe' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/business_website_status',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
                'workflow_exists'          => true,
                'workflow_status'          => 'rejected',
                'rejection_reason_message' => 'Test body'
            ],
            'status_code' => 200,
        ],
    ],

    'testNeedClarificationOnWorkflow' => [
        'request'   => [
            'url'     => '/merchant/{workflowId}/need_clarification',
            'method'  => 'PUT',
            'content' => [
                'body'    => 'needs clarification body',
                'subject' => 'needs clarification subject',
            ],
        ],
        'response'  => [
            'content'     => [
                'added_comment' => ['comment' =>  'need_clarification_comment : needs clarification body' ] ,
                'added_tag'     => 'awaiting-customer-response',
            ],
            'status_code' => 200,
        ],
    ],

    'testMerchantWorkflowDetailForMerchantWorkflowType' => [
        'request'  => [
            'content' => [
            ],
            'url'     => '/merchant/{workflowType}/details',
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
                'workflow_exists'          => true,
                'workflow_status'          => 'rejected',
                'rejection_reason_message' => 'Test body',
                'needs_clarification'      => null,
            ],
            'status_code' => 200,
        ],
    ],

    'testSubmitMerchantWorkflowClarification' => [
        'request'  => [
            'content' => [
                'merchant_workflow_clarification'       => ' Merchant test workflow clarification ',
                'clarification_documents_ids'           =>  ['doc_randomId1']
            ],
            'url'     => '/merchant/submit_clarification/{workflowType}',
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => 'https://dashboard.razorpay.com',
            ],
        ],
        'response' => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],

    'testValidateInvalidCin' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'content' => [
                'company_cin'       => 'U67190TN2014PTC096978abcdabcdbdbd',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The company cin format is invalid.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateCin' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'content' => [
                'company_cin'       => 'U67190TN2014PTC096978',
            ],
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200
        ],
    ],

    'testValidateInvalidLlpin' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'content' => [
                'company_cin'       => 'F1D3-12345',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The company cin format is invalid.',
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateLlpin' => [
        'request'  => [
            'url'     => '/merchant/activation',
            'method'  => 'POST',
            'content' => [
                'company_cin'       => 'F2A3-0001',
            ],
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200
        ],
    ],

    'testUpdateContactUniqueOwnerWithContactMobileDifferentFormatSuccess' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '+911234567890',
                'new_contact_number' => '9876543210'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ],
    ],

    'testUpdateContactMultipleOwnersSameContactMobileWithDifferentFormatFailure' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '+919876543210',
                'new_contact_number' => '1234567890'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>  PublicErrorDescription::BAD_REQUEST_MULTI_OWNER_ACCOUNTS_ASSOCIATED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' =>  ErrorCode::BAD_REQUEST_MULTI_OWNER_ACCOUNTS_ASSOCIATED,
        ],
    ],

    'testUpdateMerchantContactWithContactAlreadyExistsWithDifferentFormatFailure' => [
        'request'  => [
            'content' => [
                'old_contact_number' => '9876543210',
                'new_contact_number' => '+911234567890'
            ],
            'url'     => '/merchants/{id}/mobile',
            'method'  => 'PUT',
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' =>  PublicErrorDescription::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' =>  ErrorCode::BAD_REQUEST_CONTACT_MOBILE_ALREADY_TAKEN,
        ],
    ],



    'testSaveWebsitePlugin' => [
        'request'  => [
            'content' => [
                'website' => "https://flipkart.com",
                'plugin_name' => "wix"
            ],
            'url'     => '/onboarding/merchants/1cXSLlUU8V9sXl/plugin',
            'method'  => 'POST',
        ],
        'response' => [
            'content'     => [],
            'success'     => true,
            'status_code' => 200,
        ]
    ],

    'testMerchantWebsitePluginProducerCalled' => [
        'request'  => [
            'content' => [
                'website'           => 'www.liotec.ch',
            ],
            'url'     => '/merchant/activation',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testFetchIdentityVerificationUrl' => [
        'request'  => [
            'content' => [
                'verification_type' => 'AADHAAR_EKYC',
                'redirect_url'      => 'https://www.google.com/'
            ],
            'url'     => '/merchant/identity/verification',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'verification_url' =>  'https://api.digitallocker.gov.in/public'
                 ],
        ],
    ],

    'testFetchIdentityVerificationUrlForBVSFailure' => [
        'request'  => [
            'content' => [
                'verification_type' => 'AADHAAR_EKYC',
                'redirect_url'      => 'https://www.google.com/'
            ],
            'url'     => '/merchant/identity/verification',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'code'    => 'unavailable',
                'message' => 'hyperverge gateway request failed with http code - 500  internal code  - ER_SERVER, error - Something went wrong',
                'meta'    => [
                    "internal_error_code" => "SERVER_ERROR",
                    "public_error_code"   => "some_error_encountered"
                ]
                ],
        ],
    ],

    'testFetchIdentityVerificationUrlForInvalidInputs' => [
        'request'  => [
            'content' => [
                'verification_type' => 'AADHAAR_EKYC'
            ],
            'url'     => '/merchant/identity/verification',
            'method'  => 'POST'
        ],
        'response'  =>[
            'content' =>[
                'error'  => [
                    'code' =>  PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The redirect url field is required.',
                ],
            ],
        'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testProcessIdentityVerificationDetails' => [
        'request'  => [
            'content' => [
                'verification_type' => 'AADHAAR_EKYC',
            ],
            'url'     => '/merchant/process/identity/verificationDetails',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'is_valid' => true
            ],
        ],
    ],

    'testFailureProcessIdentityVerificationDetails' => [
        'request'  => [
            'content' => [
                'verification_type' => 'AADHAAR_EKYC',
            ],
            'url'     => '/merchant/process/identity/verificationDetails',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testProcessIdentityVerificationDetailsForBVSFailure' => [
        'request'  => [
            'content' => [
                'verification_type' => 'AADHAAR_EKYC',
            ],
            'url'     => '/merchant/process/identity/verificationDetails',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'code'    => 'unavailable',
                'message' => 'hyperverge gateway request failed with http code - 500  internal code  - ER_SERVER, error - Something went wrong',
                'meta'    => [
                    "internal_error_code" => "SERVER_ERROR",
                    "public_error_code"   => "some_error_encountered"
                ]
            ],
        ],
    ],

    'testSaveMerchantConsentNotProvided' => [
        'request' => [
            'content' => [
                'consents' => [
                    [
                        'is_provided'      => false,
                        'documents_detail' => [
                            'type'         => 'DIGILOCKER_TERMS_AND_CONDITIONS',
                            'url'          => 'https://razorpay.com/terms/'
                        ]
                    ]
                ]
            ],
            'url'     => '/merchant/consents',
            'method'  => 'POST',
        ],
        'response'  =>[
            'content' =>[
                'error'  => [
                    'code' =>  ErrorCode::BAD_REQUEST_ERROR
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_REQUEST_BODY,
        ],
    ],

    'testSaveMerchantConsentProvided' => [
        'request' => [
            'content' => [
                'consents' => [
                    [
                        'is_provided'      => true,
                        'documents_detail' => [
                            'type'         => 'DIGILOCKER_TERMS_AND_CONDITIONS',
                            'url'          => 'https://razorpay.com/terms/'
                        ]
                    ]
                ]
            ],
            'url'     => '/merchant/consents',
            'method'  => 'POST',
        ],
        'response'  =>[
            'content' =>['success' => true],
        ],
    ],

    'testCreateMerchantProductDuringMerchantActivationIfNotExist' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'partner_id'       => '10000000000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testSkipCreateMerchantProductDuringMerchantActivationIfExist' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'partner_id'       => '10000000000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'verification' => [
                    'status' => 'disabled',
                    'disabled_reason' => 'required_fields',
                ],
                'can_submit' => false,
            ],
        ],
    ],

    'testErrorWhenInvalidPartnerIdIsProvidedDuringActivation' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'partner_id'       => '10000000000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' =>[
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ],

    'testErrorWhenExpIsNotEnabledForProvidedPartnerIdDuringActivation' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'partner_id'       => '10000000000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' =>[
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PARTNER_SUBMERCHANT_WHITELABEL_ONBOARDING_EXP_NOT_ENABLED,
        ],
    ],

    'testErrorWhenResellerPartnerProvidedDuringActivation' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'partner_id'       => '10000000000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' =>[
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testErrorWhenPurePlatformPartnerProvidedDuringActivation' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'partner_id'       => '10000000000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' =>[
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_PARTNER_ACTION,
        ],
    ],

    'testErrorWhenPartnerIdProvidedIsNotPartnerDuringActivation' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002',
                'partner_id'       => '10000000000000'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' =>[
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_IS_NOT_PARTNER,
        ],
    ]

];
