<?php

use RZP\Models\Batch;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testValidateBatchPayoutLinkBulkV2CSV' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME       => 'Amit',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL      => 'amit@razorpay.com',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER     => '9876543210',
                        Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC        => 'testing',
                        Batch\Header::CONTACT_TYPE                        => 'employee',
                        Batch\Header::PAYOUT_LINK_BULK_AMOUNT             => '100',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_SMS           => 'Yes',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL         => 'Yes',
                        Batch\Header::PAYOUT_PURPOSE                      => 'refund',
                        Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID       => 'REFERENCE01',
                        Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE        => 'test key',
                        Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC         => 'test value',
                        Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                        Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutLinkBulkV2CSVOptionalHeaders' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME       => 'Amit',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL      => 'amit@razorpay.com',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER     => '9876543210',
                        Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC        => 'testing',
                        Batch\Header::CONTACT_TYPE                        => 'employee',
                        Batch\Header::PAYOUT_LINK_BULK_AMOUNT             => '100',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_SMS           => 'Yes',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL         => 'Yes',
                        Batch\Header::PAYOUT_PURPOSE                      => 'refund',
                    ],
                ],
            ],
        ],
    ],

    'testCreateBatchPayoutLinkBulkV2CSVMissingMandatoryHeader' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Uploaded file is missing mandatory header(s) [Payout Description]',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateErrorFileForBatchPayoutLinkBulkV2CSVInvalidSendSMS' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateErrorFileForBatchPayoutLinkBulkV2CSVInvalidAmount' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2CSVRandomExtraHeader' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Uploaded file has has invalid header [feature]',
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testValidateBatchPayoutLinkBulkV2CSVAmountRupeesFloatWithThreeDecimals' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForNoNotesTitle' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForNoNotesDesc' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForMissingContactNumberForSendSMS' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForMissingContactMailForSendEmail' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForMissingContactNumberAndMail' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForMissingExpireDate' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForIncorrectExpireDateFormat1' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForIncorrectExpireDateFormat2' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2FailForIncorrectExpireTimeFormat' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
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

    'testValidateBatchPayoutLinkBulkV2CSVSuccess' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME       => 'Amit',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL      => 'amit@razorpay.com',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER     => '9876543210',
                        Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC        => 'testing',
                        Batch\Header::CONTACT_TYPE                        => 'employee',
                        Batch\Header::PAYOUT_LINK_BULK_AMOUNT             => '100',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_SMS           => 'Yes',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL         => 'Yes',
                        Batch\Header::PAYOUT_PURPOSE                      => 'refund',
                        Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID       => 'REFERENCE01',
                        Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE        => 'test key',
                        Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC         => 'test value',
                        Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                        Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '10:30',
                    ],
                ],
            ],
        ],
    ],

    'testValidateBatchPayoutLinkBulkV2CSVSuccessPost12Hr' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout_link_bulk_v2'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NAME       => 'Amit',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_EMAIL      => 'amit@razorpay.com',
                        Batch\Header::PAYOUT_LINK_BULK_CONTACT_NUMBER     => '9876543210',
                        Batch\Header::PAYOUT_LINK_BULK_PAYOUT_DESC        => 'testing',
                        Batch\Header::CONTACT_TYPE                        => 'employee',
                        Batch\Header::PAYOUT_LINK_BULK_AMOUNT             => '100',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_SMS           => 'Yes',
                        Batch\Header::PAYOUT_LINK_BULK_SEND_EMAIL         => 'Yes',
                        Batch\Header::PAYOUT_PURPOSE                      => 'refund',
                        Batch\Header::PAYOUT_LINK_BULK_REFERENCE_ID       => 'REFERENCE01',
                        Batch\Header::PAYOUT_LINK_BULK_NOTES_TITLE        => 'test key',
                        Batch\Header::PAYOUT_LINK_BULK_NOTES_DESC         => 'test value',
                        Batch\Header::PAYOUT_LINK_BULK_EXPIRY_DATE         => '20/11/2020',
                        Batch\Header::PAYOUT_LINK_BULK_EXPIRY_TIME         => '15:30',
                    ],
                ],
            ],
        ],
    ]
];
