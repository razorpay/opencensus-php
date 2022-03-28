<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateSubMerchantBatchAggregator' => [
        'request'   => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit' => 1,
                    'partner_id'  => '10000000000000',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testProcessSubMerchantBatchPartnerNotDummyAllSteps' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit'        => 1,
                    'autofill_details'   => 1,
                    'use_email_as_dummy' => 0,
                    'auto_activate'      => 1,
                    'partner_id'         => '10000000000000',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testSkipBankAccountRegistration' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit'          => 1,
                    'autofill_details'     => 1,
                    'use_email_as_dummy'   => 0,
                    'auto_activate'        => 1,
                    'skip_ba_registration' => 1,
                    'partner_id'           => '10000000000000',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 1,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchPartnerNotDummySubmit' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit'        => 1,
                    'autofill_details'   => 1,
                    'use_email_as_dummy' => 0,
                    'auto_activate'      => 0,
                    'partner_id'         => '10000000000000',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchPartnerDummyEmailAllSteps' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit'      => 1,
                    'autofill_details' => 1,
                    'partner_id'       => '10000000000000',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchPartnerDummyEmailCreate' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit'      => 0,
                    'autofill_details' => 0,
                    'partner_id'       => '10000000000000',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchPartnerInvalidFileEntriesForActivate' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit'      => 1,
                    'autofill_details' => 1,
                    'partner_id'       => '10000000000000',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchPartnerInvalidInput' => [
        'request'   => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit'      => 1,
                    'autofill_details' => 'blah',
                    'partner_id'       => '10000000000000',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The autofill details field must be true or false.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubMerchantBatchPartner' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit' => 1,
                    'partner_id'  => '10000000000000',
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateSubMerchantBatchInvalidHeaders' => [
        'request'   => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'auto_submit' => 1,
                    'partner_id'  => '10000000000000',
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid headers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testProcessSubMerchantBatchInstantActivation' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'instantly_activate'        => 1,
                    'partner_id'                => '10000000000000',
                    'use_email_as_dummy'        => 0,
                    'auto_enable_international' => 1,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateSubMerchantBatchWithActivatedMccPending' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'partner_id'                => '10000000000000',
                    'use_email_as_dummy'        => 1,
                    'auto_activate'             => 1,
                    'auto_submit'               => 1,
                    'skip_ba_registration'      => 1,
                    'auto_enable_international' => 1,
                    'autofill_details'          => 1,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testCreateSubMerchantBatchAndRunDedupeWithInvalidPermission' => [
        'request'   => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'partner_id'                => '10000000000000',
                    'use_email_as_dummy'        => 1,
                    'auto_activate'             => 1,
                    'auto_submit'               => 1,
                    'skip_ba_registration'      => 1,
                    'auto_enable_international' => 1,
                    'autofill_details'          => 1,
                    'dedupe'                    => 1,
                ]
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND,
        ],
    ],

    'testProcessSubMerchantBatchForNotEnablingInternational' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'partner_id'         => '10000000000000',
                    'use_email_as_dummy' => 0,
                    'auto_submit'        => 1,
                    'auto_activate'      => 1,
                    'autofill_details'   => 1,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchForEnablingInternational' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'partner_id'                => '10000000000000',
                    'use_email_as_dummy'        => 0,
                    'auto_submit'               => 1,
                    'auto_activate'             => 1,
                    'autofill_details'          => 1,
                    'auto_enable_international' => 1,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchForUnregisteredMerchants' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'partner_id'                => '10000000000000',
                    'use_email_as_dummy'        => 0,
                    'auto_submit'               => 1,
                    'auto_activate'             => 1,
                    'autofill_details'          => 1,
                    'auto_enable_international' => 1,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 1,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchForActivateAlreadyExistingMerchant' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'partner_id'         => '10000000000000',
                    'use_email_as_dummy' => 0,
                    'autofill_details'   => 1,
                    'instantly_activate' => 1,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 3,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'testProcessSubMerchantBatchForEditingSubMerchantDetails' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type'   => 'sub_merchant',
                'config' => [
                    'partner_id'         => '10000000000000',
                    'use_email_as_dummy' => 0,
                    'autofill_details'   => 1,
                    'instantly_activate' => 0,
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity'           => 'batch',
                'type'             => 'sub_merchant',
                'status'           => 'created',
                'total_count'      => 1,
                'success_count'    => 0,
                'failure_count'    => 0,
                'attempts'         => 0,
                'processed_amount' => 0,
                'processed_at'     => null,
            ],
        ],
    ],

    'defaultEntries' => [
        [
            Header::MERCHANT_NAME            => 'SubMerchantone',
            Header::MERCHANT_EMAIL           => 'merch1@razorpay.com',
            Header::CONTACT_NAME             => 'merch',
            Header::CONTACT_EMAIL            => 'merch1@razorpay.com',
            Header::CONTACT_MOBILE           => '9302930211',
            Header::TRANSACTION_REPORT_EMAIL => 'merch1@razorpay.com',
            Header::ORGANIZATION_TYPE        => 3,
            Header::BUSINESS_NAME            => 'sub merch business',
            Header::BILLING_LABEL            => 'acme',
            Header::INTERNATIONAL            => 0,
            Header::PAYMENTS_FOR             => 'business',
            Header::BUSINESS_MODEL           => 'acme',
            Header::BUSINESS_CATEGORY        => 'financial_services',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'KA',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'KA',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z5',
            Header::PROMOTER_PAN             => 'KDOPK0930L',
            Header::WEBSITE_URL              => 'http://www.facebook.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789098',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000077',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::FEE_BEARER               => 'service id',
            Header::COMPANY_CIN              => 'U65999KA2018PTC114468',
            Header::COMPANY_PAN              => 'JFKCU3829K',
            Header::COMPANY_PAN_NAME         => 'dsfdfsd',
            Header::MERCHANT_ID              => '',
        ],
        [
            Header::MERCHANT_NAME            => 'SubMerchanttwo',
            Header::MERCHANT_EMAIL           => 'merch2@razorpay.com',
            Header::CONTACT_NAME             => 'merch',
            Header::CONTACT_EMAIL            => 'merch2@razorpay.com',
            Header::CONTACT_MOBILE           => '9302930212',
            Header::TRANSACTION_REPORT_EMAIL => 'merch2@razorpay.com',
            Header::ORGANIZATION_TYPE        => 3,
            Header::BUSINESS_NAME            => 'sub merch business',
            Header::BILLING_LABEL            => 'acme',
            Header::INTERNATIONAL            => 0,
            Header::PAYMENTS_FOR             => 'business',
            Header::BUSINESS_MODEL           => 'acme',
            Header::BUSINESS_CATEGORY        => 'financial_services',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'KA',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'Ka',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z6',
            Header::PROMOTER_PAN             => 'KDOPK0930L',
            Header::WEBSITE_URL              => 'http://www.facebook.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789099',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000056',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::FEE_BEARER               => 'service id',
            Header::COMPANY_CIN              => 'U65999KA2018PTC114468',
            Header::COMPANY_PAN              => 'JFKCU3829K',
            Header::COMPANY_PAN_NAME         => 'dsfdfsd',
            Header::MERCHANT_ID              => '',
        ],
        [
            Header::MERCHANT_NAME            => 'SubMerchantthree',
            Header::MERCHANT_EMAIL           => 'merch3@razorpay.com',
            Header::CONTACT_NAME             => 'merch',
            Header::CONTACT_EMAIL            => 'merch3@razorpay.com',
            Header::CONTACT_MOBILE           => '9302930213',
            Header::TRANSACTION_REPORT_EMAIL => 'merch3@razorpay.com',
            Header::ORGANIZATION_TYPE        => 3,
            Header::BUSINESS_NAME            => 'sub merch business',
            Header::BILLING_LABEL            => 'acme',
            Header::INTERNATIONAL            => 0,
            Header::PAYMENTS_FOR             => 'business',
            Header::BUSINESS_MODEL           => 'acme',

            // whitelisted activation flow
            Header::BUSINESS_CATEGORY        => 'education',
            Header::BUSINESS_SUB_CATEGORY    => 'college',

            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'KA',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'KA',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z7',
            Header::PROMOTER_PAN             => 'KDOPK0930L',
            Header::WEBSITE_URL              => 'http://www.facebook.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789090',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000011',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::FEE_BEARER               => 'service id',
            Header::COMPANY_CIN              => 'U65999KA2018PTC114468',
            Header::COMPANY_PAN              => 'JFKCU3829K',
            Header::COMPANY_PAN_NAME         => 'dsfdfsd',
            Header::MERCHANT_ID              => '',
        ],
    ],

    'subMerchantEntry'  => [
        [
            Header::MERCHANT_NAME            => 'SubMerchantone',
            Header::MERCHANT_EMAIL           => 'merch1@razorpay.com',
            Header::CONTACT_NAME             => 'merch',
            Header::CONTACT_EMAIL            => 'merch1@razorpay.com',
            Header::CONTACT_MOBILE           => '9302930211',
            Header::TRANSACTION_REPORT_EMAIL => 'merch1@razorpay.com',
            Header::ORGANIZATION_TYPE        => 3,
            Header::BUSINESS_NAME            => 'sub merch business',
            Header::BILLING_LABEL            => 'acme',
            Header::INTERNATIONAL            => 0,
            Header::PAYMENTS_FOR             => 'business',
            Header::BUSINESS_MODEL           => 'acme',
            Header::BUSINESS_CATEGORY        => 'financial_services',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'KA',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'KA',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z5',
            Header::PROMOTER_PAN             => 'KDOPK0930L',
            Header::WEBSITE_URL              => 'http://www.facebook.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789098',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000077',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::FEE_BEARER               => 'service id',
            Header::COMPANY_CIN              => 'U65999KA2018PTC114468',
            Header::COMPANY_PAN              => 'JFKCU3829K',
            Header::COMPANY_PAN_NAME         => 'dsfdfsd',
            Header::MERCHANT_ID              => '',
        ],
    ],

    'subMerchantUpdateEntry' => [
       [
            Header::MERCHANT_NAME            => 'SubMerchantone',
            Header::MERCHANT_EMAIL           => 'merch1@razorpay.com',
            Header::CONTACT_NAME             => 'merchx',
            Header::CONTACT_EMAIL            => 'merch1@razorpay.com',
            Header::CONTACT_MOBILE           => '9302930211',
            Header::TRANSACTION_REPORT_EMAIL => 'merch1@razorpay.com',
            Header::ORGANIZATION_TYPE        => 3,
            Header::BUSINESS_NAME            => 'sub merchx business',
            Header::BILLING_LABEL            => 'acme',
            Header::INTERNATIONAL            => 0,
            Header::PAYMENTS_FOR             => 'business',
            Header::BUSINESS_MODEL           => 'acme',
            Header::BUSINESS_CATEGORY        => 'financial_services',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'KA',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'KA',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z5',
            Header::PROMOTER_PAN             => 'KDOPK0930L',
            Header::WEBSITE_URL              => 'http://www.facebook.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789098',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000077',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::FEE_BEARER               => 'service id',
            Header::COMPANY_CIN              => 'U65999KA2018PTC114468',
            Header::COMPANY_PAN              => 'JFKCU3829K',
            Header::COMPANY_PAN_NAME         => 'dsfdfsd',
            Header::MERCHANT_ID              => '',
       ],
    ],

    'skipBankAccountRegistrationEntries' => [
        [
            Header::MERCHANT_NAME            => 'SubMerchantthree',
            Header::MERCHANT_EMAIL           => 'merch3@razorpay.com',
            Header::CONTACT_NAME             => 'merch',
            Header::CONTACT_EMAIL            => 'merch3@razorpay.com',
            Header::CONTACT_MOBILE           => '9302930213',
            Header::TRANSACTION_REPORT_EMAIL => 'merch3@razorpay.com',
            Header::ORGANIZATION_TYPE        => 3,
            Header::BUSINESS_NAME            => 'sub merch business',
            Header::BILLING_LABEL            => 'acme',
            Header::INTERNATIONAL            => 0,
            Header::PAYMENTS_FOR             => 'business',
            Header::BUSINESS_MODEL           => 'acme',

            // whitelisted activation flow
            Header::BUSINESS_CATEGORY        => 'education',
            Header::BUSINESS_SUB_CATEGORY    => 'college',

            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'KA',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'KA',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z7',
            Header::PROMOTER_PAN             => 'KDOPK0930L',
            Header::WEBSITE_URL              => 'http://www.test.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '',
            Header::BANK_BRANCH_IFSC         => '',
            Header::BANK_ACCOUNT_NAME        => '',
            Header::FEE_BEARER               => 'service id',
            Header::COMPANY_CIN              => 'U65999KA2018PTC114468',
            Header::COMPANY_PAN              => 'JFKCU3829K',
            Header::COMPANY_PAN_NAME         => 'dsfdfsd',
        ],
    ],

    'UnregisteredEntries' => [
        [
            Header::MERCHANT_NAME            => 'SubMerchanttwo',
            Header::MERCHANT_EMAIL           => 'merch2@razorpay.com',
            Header::CONTACT_NAME             => 'merch',
            Header::CONTACT_EMAIL            => 'merch2@razorpay.com',
            Header::CONTACT_MOBILE           => '9302930212',
            Header::TRANSACTION_REPORT_EMAIL => 'merch2@razorpay.com',
            Header::ORGANIZATION_TYPE        => 2,
            Header::BUSINESS_NAME            => 'sub merch business',
            Header::BILLING_LABEL            => 'acme',
            Header::INTERNATIONAL            => 0,
            Header::PAYMENTS_FOR             => 'business',
            Header::BUSINESS_MODEL           => 'acme',
            Header::BUSINESS_CATEGORY        => 'financial_services',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'KA',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'KA',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z6',
            Header::PROMOTER_PAN             => 'KDOPK0930L',
            Header::WEBSITE_URL              => 'http://www.test.com',
            Header::PROMOTER_PAN_NAME        => 'Test123',
            Header::BANK_ACCOUNT_NUMBER      => '123456789099',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000056',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::FEE_BEARER               => 'service id',
            Header::COMPANY_CIN              => 'U65999KA2018PTC114468',
            Header::COMPANY_PAN              => 'JFKCU3829K',
            Header::COMPANY_PAN_NAME         => 'dsfdfsd',
        ],
    ]


];
