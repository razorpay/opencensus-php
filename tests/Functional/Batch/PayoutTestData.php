<?php

use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testValidateErrorDescriptionsInBatchPayoutsCSV' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 4,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '40',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Chirag Chiranjib',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_NAME_2            => 'Chirag Chiranjib',
                        Batch\Header::CONTACT_EMAIL_2           => 'chirag.chiranjib@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                        Batch\Header::NOTES_CODE                => 'test',
                        Batch\Header::NOTES_PLACE               => 'Bhubaneswar'
                    ],
                ],
            ],
        ],

    ],
    'testValidateUtf8EncodingInBatchFundAccountsCSV' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'fund_account',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 3,
                'parsed_entries' => [
                    [
                        Batch\Header::FUND_ACCOUNT_TYPE => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME => 'Sagnik Saha',
                        Batch\Header::FUND_ACCOUNT_IFSC => 'SBIN0007679',
                        Batch\Header::FUND_ACCOUNT_NUMBER => '200200200200',
                        Batch\Header::FUND_ACCOUNT_VPA => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::FUND_ACCOUNT_PROVIDER     => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_ID                => '',
                        Batch\Header::CONTACT_TYPE              => 'vendor',
                        Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                        Batch\Header::CONTACT_EMAIL_2           => "sagnik3012@gmail.com",
                        Batch\Header::CONTACT_MOBILE_2          => '9876543210',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                        Batch\Header::NOTES_CODE                => 'test',
                        Batch\Header::NOTES_PLACE               => 'Kolkata'
                    ],
                ],
            ],
        ],
    ],
    'testValidateUtf8EncodingInBatchPayoutsCSV' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout',
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 3,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '40',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_NAME_2            => 'Sagnik Saha',
                        Batch\Header::CONTACT_EMAIL_2           => 'sagnik.saha@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                        Batch\Header::NOTES_CODE                => 'test',
                        Batch\Header::NOTES_PLACE               => 'Kolkata'
                    ],
                ],
            ],
        ],

    ],
    'testValidateBatchPayoutsCSV' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVForAmazonPayPayout' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'amazonpay',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => '',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsXLSX' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.23,
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => null,
                        Batch\Header::FUND_ACCOUNT_ID           => null,
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => null,
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => null,
                        Batch\Header::FUND_ACCOUNT_EMAIL        => null,
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => null,
                        Batch\Header::CONTACT_REFERENCE_ID      => null,
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsXLSXForAmazonPayPayout' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.23,
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'amazonpay',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => null,
                        Batch\Header::FUND_ACCOUNT_ID           => null,
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => null,
                        Batch\Header::FUND_ACCOUNT_NUMBER       => null,
                        Batch\Header::FUND_ACCOUNT_VPA          => null,
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => null,
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => null,
                        Batch\Header::CONTACT_REFERENCE_ID      => null,
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsXLSXOptionalHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::FUND_ACCOUNT_ID           => null,
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => null,
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => null,
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsXLSXWithoutAmazonPayHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10.23,
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => null,
                        Batch\Header::FUND_ACCOUNT_ID           => null,
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => null,
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => null,
                        Batch\Header::CONTACT_REFERENCE_ID      => null,
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVOptionalHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVWithoutAmazonPayHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsXLSXMissingMandatoryHeader' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file you are trying to upload is missing one of the mandatory/conditionally mandatory header',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_BATCH_FILE_MISSING_MANDATORY_HEADERS,
        ],
    ],

    'testCreateBatchPayoutsCSVMissingMandatoryHeader' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The file you are trying to upload is missing one of the mandatory/conditionally mandatory header',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYOUT_BATCH_FILE_MISSING_MANDATORY_HEADERS,
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInPaiseForNewUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered in Rupees format instead of Paise format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsXLSXAmountInPaiseForNewUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered in Rupees format instead of Paise format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsXLSXAmountInRupeesForExistingBulkPaiseUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered in Paise format instead of Rupees format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInPaiseForExistingBulkPaiseUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT             => '1000',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInPaiseForExistingBulkRupeesUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered in Rupees format instead of Paise format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsXLSXAmountInRupeesForExistingBulkRupeesUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => 10,
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => null,
                        Batch\Header::FUND_ACCOUNT_ID           => null,
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => null,
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => null,
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => null,
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => null,
                        Batch\Header::CONTACT_REFERENCE_ID      => null,
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInRupeesForExistingBulkRupeesUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsXLSXBothTypeOfAmountHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered either in Rupees format or in Paise format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateErrorFileForBatchPayoutsCSVNewUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsCSVExistingBulkPaiseUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsCSVExistingBulkRupeesUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsXLSXNewUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsXLSXExistingBulkRupeesUser' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVRandomExtraHeader' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The uploaded file has invalid header: feature',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsCSVPayoutAmountRupeesFloatWithThreeDecimals' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInRupeesNewUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered in Paise format instead of Rupees format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInRupeesExistingBulkRupeesUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered in Paise format instead of Rupees format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInPaiseNewUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT             => '1000',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVAmountInPaiseExistingBulkRupeesUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT             => '1000',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsCSVNewUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsCSVExistingBulkPaiseUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsCSVExistingBulkRupeesUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => []
            ],
        ],
    ],

    'testValidateErrorFileForBatchPayoutsCSVAmountInRupeesExistingBulkRupeesUserExperimentOff' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'You seem to have entered a wrong amount header. The amount has to be entered in Paise format instead of Rupees format',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutsCSVFundAccountStartingWith0' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '00100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVForAmazonPayPhoneNumberWithoutExtension' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'amazonpay',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => '',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '8124632237',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVForAmazonPayPhoneNumberWithExtension' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'amazonpay',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Mehul Kaushik',
                        Batch\Header::FUND_ACCOUNT_IFSC         => '',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVForAmazonPayPhoneNumberWithExtensionAndFormulaeInjection' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'amazonpay',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::PAYOUT_NARRATION          => 'test123',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'wallet',
                        Batch\Header::FUND_ACCOUNT_NAME         => '"\'=SUM(A1,A2)"',
                        Batch\Header::FUND_ACCOUNT_IFSC         => '',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '+918124632237',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => 'sample@example.com',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_NAME_2            => 'Mehul Kaushik',
                        Batch\Header::CONTACT_EMAIL_2           => 'mehul.kaushik@razorpay.com',
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutsCSVForAmazonPayEmptyPhoneNumber' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 0,
                'error_count'       => 1,
                'parsed_entries'    => [
                ],
            ],
        ],
    ],

    'testLedgerTransactorEventAmounts' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/payouts/bulk',
            'content' => [
                [
                    'razorpayx_account_number' => '2224440041626905',
                    'payout'                   => [
                        'amount'           => '',
                        'amount_in_rupees' => '17858.92',
                        'currency'         => 'INR',
                        'mode'             => 'IMPS',
                        'purpose'          => 'payout',
                        'narration'        => 'Acme Corp Fund Transfer',
                        'reference_id'     => 'MFN1234'
                    ],
                    'fund'                     => [
                        'account_type'   => 'bank_account',
                        'account_name'   => 'Gaurav Kumar',
                        'account_IFSC'   => 'HDFC0001234',
                        'account_number' => '1121431121541121',
                    ],
                    'contact'                  => [
                        'type'         => 'customer',
                        'name'         => 'Gaurav Kumar',
                        'email'        => 'sampleone@example.com',
                        'mobile'       => '9988998899',
                        'reference_id' => ''
                    ],
                    'idempotency_key'          => 'batch_abc123'
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 1,
                'items'  => [
                    [
                        'batch_id'        => 'batch_C3fzDCb4hA4F6b',
                        'idempotency_key' => 'batch_abc123'
                    ],
                ],
            ],
        ],
    ],

    'testBalanceFetchedFromLedgerForBulkPayoutAmountValidationOnReverseShadow' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout',
            ],
        ],
        'response' => [
            'content'     => [
                'processable_count' => 2,
                'error_count' => 0,
                'parsed_entries' => [
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '10',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik Saha',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                        Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_EMAIL_2           => "sagnik.saha@razorpay.com",
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                        Batch\Header::NOTES_CODE                => 'test',
                        Batch\Header::NOTES_PLACE               => 'Kolkata'
                    ],
                    [
                        Batch\Header::RAZORPAYX_ACCOUNT_NUMBER  => '2323230041626905',
                        Batch\Header::PAYOUT_AMOUNT_RUPEES      => '40',
                        Batch\Header::PAYOUT_CURRENCY           => 'INR',
                        Batch\Header::PAYOUT_MODE               => 'NEFT',
                        Batch\Header::PAYOUT_PURPOSE            => 'refund',
                        Batch\Header::FUND_ACCOUNT_ID           => '',
                        Batch\Header::FUND_ACCOUNT_TYPE         => 'bank_account',
                        Batch\Header::FUND_ACCOUNT_NAME         => 'Sagnik S',
                        Batch\Header::FUND_ACCOUNT_IFSC         => 'SBIN0010720',
                        Batch\Header::FUND_ACCOUNT_NUMBER       => '100200300400',
                        Batch\Header::FUND_ACCOUNT_VPA          => '',
                        Batch\Header::FUND_ACCOUNT_PHONE_NUMBER => '',
                        Batch\Header::CONTACT_NAME_2            => "Sagnik Saha",
                        Batch\Header::PAYOUT_NARRATION          => 'NarrationTest',
                        Batch\Header::PAYOUT_REFERENCE_ID       => '',
                        Batch\Header::FUND_ACCOUNT_EMAIL        => '',
                        Batch\Header::CONTACT_TYPE              => 'employee',
                        Batch\Header::CONTACT_EMAIL_2           => "sagnik.s@razorpay.com",
                        Batch\Header::CONTACT_MOBILE_2          => '',
                        Batch\Header::CONTACT_REFERENCE_ID      => '',
                        Batch\Header::NOTES_CODE                => 'test',
                        Batch\Header::NOTES_PLACE               => 'Kolkata'
                    ],
                ],
            ],
        ],
    ],

    'testValidateBulkPayoutsWithOldTemplate' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
                'type' => 'payout',
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 301,
        ],

    ],

    'testValidateBulkPayoutsWithAmazonPayWithBeneDetail' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testValidateBulkPayoutsWithUPIWithBeneDetail' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testValidateBulkPayoutsWithBankTransferWithBeneDetail' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testValidateBulkPayoutsWithAmazonPayWithBeneId' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testValidateBulkPayoutsWithBankTransferWithBeneId' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testValidateBulkPayoutsWithUPIWithBeneId' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testValidateEmptyBatchFile' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'File upload failed, atleast 1 filled row required',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
        ],
    ],

    'testInvalidBankTransferBeneIdBatchFile' => [
        'request'  => [
            'url'     => '/payouts/batch/validate',
            'method'  => 'post',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => "File upload failed, file format doesn't follow any of the templates",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILED,
        ],
    ],

    'testGetBatchRowsWithCreatorEmailForTypePayouts' => [
        'request'  => [
            'url'     => '/batches?type=payout',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testGetBatchRowsWithCreatorNameForTypePayouts' => [
        'request'  => [
            'url'     => '/batches?type=payout',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testGetBatchRowsWithCreatorNameForTypePaymentLinks' => [
        'request'  => [
            'url'     => '/batches?type=payment_link',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testGetBatchRowsWithCreatorEmailForTypePaymentLinks' => [
        'request'  => [
            'url'     => '/batches?type=payment_link',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],

    'testGetBatchDetails' => [
        'request'  => [
            'url'     => '/batches/batch_C3fzDCb4hA4F6b',
            'method'  => 'get',
            'server' => [
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Request-Origin' => config('applications.banking_service_url')
            ],
            'content' => [

            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ],

    ],
];
