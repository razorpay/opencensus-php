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
            ],
        ],
    ],

    'testUpdateIfscCode' => [
        'request' => [
            'content' => [
                'bank_branch_ifsc' => 'ICIC0000002'
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

    'testSubmit' => [
        'request'  => [
            'content' => [
                'submit' => true
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
                'bank_branch_ifsc' => 'ICIC000000'
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
                'bank_branch_ifsc' => 'ICIC0000001'
            ],
            'url' => '/merchant/activation',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Activation form has been locked for editing by admin.',
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
                RejectionReasons::UNSUPPORTED_BUSINESS_MODEL => [
                    [
                        RejectionReasons::CODE        => RejectionReasons::WEB_DEVELOPMENT_OR_WEB_HOSTING,
                        RejectionReasons::DESCRIPTION => RejectionReasons::WEB_DEVELOPMENT_OR_WEB_HOSTING_DESCRIPTION,
                    ],
                ],
                RejectionReasons::OTHERS => [
                    [
                        RejectionReasons::CODE        => RejectionReasons::DUPLICATE_OR_ERRENOUS_CREATION,
                        RejectionReasons::DESCRIPTION => RejectionReasons::DUPLICATE_OR_ERRENOUS_CREATION_DESCRIPTION,
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

    'testMerchantDetailsPatch' => [
        'request'  => [
            'content' => [
                'business_operation_address'    => 'Test address',
                'business_operation_state'      => 'Karnataka',
                'business_operation_city'       => 'Bengaluru',
                'business_operation_pin'        => '560030',
                'business_category'             => 'financial_services',
                'business_subcategory'          => 'lending',
                'international_activation_flow' => 'whitelist',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_operation_address'    => 'Test address',
                'business_operation_state'      => 'Karnataka',
                'business_operation_city'       => 'Bengaluru',
                'business_operation_pin'        => '560030',
                'business_category'             => 'financial_services',
                'business_subcategory'          => 'lending',
                'international_activation_flow' => 'whitelist',
            ],
        ],
    ],

    'testMerchantDetailsPatchMerchantContextNotSet' => [
        'request'  => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'Karnataka',
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
                'business_operation_state'   => 'Karnataka',
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

    'testMerchantDetailsPatchValidStatusChange' => [
        'request'  => [
            'content' => [
                'bank_details_verification_status' => 'verified',
                'poa_verification_status'          => 'verified'
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content'     => [
                'bank_details_verification_status' => 'verified',
                'poa_verification_status'          => 'verified'
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
                'business_operation_state'   => 'Karnataka',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
            ],
            'url'     => '/merchants/details',
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'Karnataka',
                'business_operation_city'    => 'Bengaluru',
                'business_operation_pin'     => '560030',
            ],
        ],
    ],

    'testMerchantDetailsPatchBusinessModel' => [
        'request'  => [
            'content' => [
                'business_operation_address' => 'Test address',
                'business_operation_state'   => 'Karnataka',
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
                'business_operation_state'   => 'Karnataka',
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
                    'activation_progress'   => 0,
                    'can_submit'            => false,
                    'steps_finished'        => [],
                    'activated'             => 0,
                    'verification'          => [
                        'status'                => 'disabled',
                        'disabled_reason'       => 'required_fields',
                        'activation_progress'   => 5,
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
                'bank_branch_ifsc' => 'ICIC0000002',
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
                            'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
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
                            'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
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
                            'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
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
                            'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
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
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
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
                    'reason'      => 'Lorem ipsum dolor sit amet consectetuer',
                ]],
                'business_description' => [[
                    'reason_type' => 'predefined',
                    'field_type'  => 'text',
                    'reason_code' => 'provide_poc',
                ]],
            ],
        ],
    ],

];
