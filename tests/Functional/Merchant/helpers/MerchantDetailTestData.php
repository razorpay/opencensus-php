<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
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

    'testDefaultInstrumentRequestOnMerchantActivation' => [
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
            ],
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

    'testSmartDashboardMerchantDetailsPatch' => [
        'request'  => [
            'content' => [
                'merchant_details|business_operation_address'                      => 'Test address',
                'merchant_details|business_operation_state'                        => 'KA',
                'merchant_details|business_operation_city'                         => 'Bengaluru',
                'merchant_details|business_operation_pin'                          => '560030',
                'merchant_details|merchant|website'                                => 'https://www.test.com',
                'merchant_details|merchant_business_detail|app_urls|playstore_url' => 'https://play.google.com/store/apps/details?id=com.razorpay.payments.app.dummy'
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
                'contact_mobile'     => '8722627189',
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
                'contact_mobile'     => '9087654321',
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
                'contact_mobile'     => '9087654321',
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
                'contact_mobile'     => '9087654321',
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
                'contact_mobile'     => '9087654321',
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
                'contact_mobile' => '8722627189',
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
];
