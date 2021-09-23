<?php

use RZP\Models\Batch\Header;
use RZP\Error\ErrorCode;

return [
    'testTallyPayoutBatchValidate' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'tally_payout'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 8,
                'parsed_entries'    => [
                    [
                        Header::RAZORPAYX_ACCOUNT_NUMBER => '2323230041626905',
                        Header::PAYOUT_PURPOSE           => 'refund',
                        Header::PAYOUT_REFERENCE_ID      => 'test_reference_id',
                        Header::PAYOUT_MODE              => 'NEFT',
                        Header::PAYOUT_AMOUNT_RUPEES     => '1.78',
                        Header::PAYOUT_CURRENCY          => 'INR',
                        Header::PAYOUT_DATE              => '12/02/2021',
                        Header::PAYOUT_NARRATION         => 'test narration',
                        Header::FUND_ACCOUNT_TYPE        => 'bank_account',
                        Header::FUND_ACCOUNT_NAME        => 'Test Batch FA',
                        Header::FUND_ACCOUNT_IFSC        => 'SBIN0010720',
                        Header::FUND_ACCOUNT_NUMBER      => '00100200300400',
                        Header::FUND_ACCOUNT_VPA         => '',
                        Header::CONTACT_NAME_2           => 'Test Contact Batch',
                        Header::CONTACT_TYPE             => 'employee',
                        Header::CONTACT_ADDRESS          => '',
                        Header::CONTACT_CITY             => '',
                        Header::CONTACT_ZIPCODE          => '',
                        Header::CONTACT_STATE            => '',
                        Header::CONTACT_EMAIL_2          => 'testcontact@batch.com',
                        Header::CONTACT_MOBILE_2         => '',
                        Header::NOTES_STR_VALUE          => '',
                    ],
                ],
            ],
        ],
    ],

    'testCreateAdminBatchWithoutRequiredPermission' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/admin/batches',
            'content' => [
                'type' => 'tally_payout',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'Required permission not found',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND
        ],
    ],

    'testCreateAdminBatchWithPermission' => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/admin/batches',
            'content' => [
                'type' => 'tally_payout',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'description' => 'The file field is required when file id is not present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ]

];
