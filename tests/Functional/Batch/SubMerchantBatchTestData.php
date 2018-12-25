<?php

use RZP\Error\ErrorCode;
use RZP\Models\Batch\Header;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateSubMerchantBatchAggregator' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CANNOT_ADD_SUBMERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testProcessSubMerchantBatchPartnerNotDummyAllSteps' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
                'autofill_details' => 1,
                'use_email_as_dummy' => 0,
                'auto_activate' => 1,
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

    'testProcessSubMerchantBatchPartnerNotDummySubmit' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
                'autofill_details' => 1,
                'use_email_as_dummy' => 0,
                'auto_activate' => 0,
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
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
                'autofill_details' => 1,
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
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 0,
                'autofill_details' => 0,
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
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
                'autofill_details' => 1,
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
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
                'autofill_details' => 'blah',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The autofill details field must be true or false.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateSubMerchantBatchPartner' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
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
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'        => 'sub_merchant',
                'auto_submit' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid headers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
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
            Header::BUSINESS_CATEGORY        => 'finance',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'karnataka',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'karnataka',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z5',
            Header::PROMOTER_PAN             => 'KDOEK0930L',
            Header::WEBSITE_URL              => 'http://www.test.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789098',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000077',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::REFERENCE1               => 'service id',
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
            Header::BUSINESS_CATEGORY        => 'finance',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'karnataka',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'karnataka',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z6',
            Header::PROMOTER_PAN             => 'KDOEK0930L',
            Header::WEBSITE_URL              => 'http://www.test.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789099',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000056',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::REFERENCE1               => 'service id',
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
            Header::BUSINESS_CATEGORY        => 'finance',
            Header::BUSINESS_SUB_CATEGORY    => 'lending',
            Header::REGISTERED_ADDRESS       => 'acme',
            Header::REGISTERED_CITY          => 'bangalore',
            Header::REGISTERED_STATE         => 'karnataka',
            Header::REGISTERED_PINCODE       => '849583',
            Header::OPERATIONAL_ADDRESS      => 'acme',
            Header::OPERATIONAL_CITY         => 'bangalore',
            Header::OPERATIONAL_STATE        => 'karnataka',
            Header::OPERATIONAL_PINCODE      => '930293',
            Header::DOE                      => '1990-02-12',
            Header::GSTIN                    => '22AAAAA0000A1Z7',
            Header::PROMOTER_PAN             => 'KDOEK0930L',
            Header::WEBSITE_URL              => 'http://www.test.com',
            Header::PROMOTER_PAN_NAME        => 'sdfds',
            Header::BANK_ACCOUNT_NUMBER      => '123456789090',
            Header::BANK_BRANCH_IFSC         => 'HDFC0000011',
            Header::BANK_ACCOUNT_NAME        => 'Mr merch',
            Header::REFERENCE1               => 'service id',
        ],
    ]
];
