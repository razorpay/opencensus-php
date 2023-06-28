<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateBatchOfFundAccountType' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'fund_account',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'type'        => 'fund_account',
                'status'      => 'created',
                'total_count' => 5,
            ],
        ],
    ],

    'testCreateBatchOfPayoutType' => [
        'request' => [
            'url'     => '/batches',
            'method'  => 'post',
            'content' => [
                'type'  => 'payout',
                'otp'   => '0007',
                'token' => '12345678900000',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'type'        => 'payout',
                'status'      => 'created',
                'total_count' => 4,
            ],
        ],
    ],


    'setupPaymentPageWithItemsForBatchValidate' => [
        'request'  => [
            'url'     => '/payment_pages',
            'method'  => 'post',
            'content' => [
                'title'         => 'Sample title',
                "settings" => [
                    "udf_schema"    => "[{\"name\":\"email\",\"required\":true,\"title\":\"Email\",\"type\":\"string\",\"pattern\":\"email\",\"settings\":{\"position\":1}},{\"name\":\"pri__ref__id\",\"title\":\"Phone\",\"required\":true,\"type\":\"number\",\"pattern\":\"phone\",\"minLength\":\"8\",\"options\":{},\"settings\":{\"position\":2}},{\"name\":\"phone\",\"required\":true,\"title\":\"contact\",\"type\":\"number\",\"pattern\":\"phone\",\"settings\":{\"position\":3}},{\"name\":\"sec__ref__id_1\",\"required\":true,\"title\":\"DOB\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":4}},{\"name\":\"address\",\"required\":false,\"title\":\"Address 2\",\"type\":\"string\",\"pattern\":\"phone\",\"settings\":{\"position\":5}}]",
                ],
                'description'   => '[{"insert":"Sample description"},{"insert":"\\n"}]',
                'view_type' => 'file_upload_page',
                'payment_page_items' => [
                    [
                        'item' => [
                            'name'        =>  'amount',
                            'description' => NULL,
                            'amount'      => NULL,
                            'currency'    => 'INR',
                        ],
                        'mandatory'         => TRUE,
                        'image_url'         => 'dummy',
                        'stock'             => 10000,
                        'min_purchase'      => 2,
                        'max_purchase'      => 10000,
                        'min_amount'        => NULL,
                        'max_amount'        => NULL,
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [],
            'status_code' => 200,
        ],
    ],

    'testFormBuilderBatchValidatePositive1' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_page',
                'config' => [
                    'payment_page_id' => '123'
                ]
            ],
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ]
    ],


    'testFormBuilderBatchValidatePositive2' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_page',
                'config' => [
                    'payment_page_id' => '123'
                ]
            ],
        ],
        'response' => [
            'content'     => [

            ],
            'status_code' => 200,
        ]
    ],

    'testFormBuilderBatchValidateNegative1' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_page',
                'config' => [
                    'payment_page_id' => '123'
                ]
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],


    'testFormBuilderBatchValidateNegative2' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'payment_page',
                'config' => [
                    'payment_page_id' => '123'
                ]
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
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_BATCH_FILE_INVALID_HEADERS,
        ],
    ],

    'testCreateBatchOfFundAccountTypeRequestFileEntries' => [
        // Expected to create new bank account & new contact.
        [
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567890',
            'Fund Account Vpa'          => '',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expected to create new bank account & new contact.
        [
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567891',
            'Fund Account Vpa'          => '',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'P YV',
            'Contact Email'             => 'yv@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expected to use existing bank account.
        [
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567890',
            'Fund Account Vpa'          => '',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expected to create a vpa and use existing contact.
        [
            'Fund Account Type'         => 'vpa',
            'Fund Account Name'         => '',
            'Fund Account Ifsc'         => '',
            'Fund Account Number'       => '',
            'Fund Account Vpa'          => 'jitendrakkkk@upi',
            'Contact Id'                => 'cont_00000000000001',
            'Contact Type'              => '',
            'Contact Name'              => '',
            'Contact Email'             => '',
            'Contact Mobile'            => '',
            'Contact Reference Id'      => '',
            'notes[place]'              => '',
            'notes[code]'               => '',
        ],
        // Expected to create a vpa and new contact.
        [
            'Fund Account Type'         => 'vpa',
            'Fund Account Name'         => '',
            'Fund Account Ifsc'         => '',
            'Fund Account Number'       => '',
            'Fund Account Vpa'          => 'jitendrakkkk@upi',
            'Contact Id'                => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
    ],

    'testCreateBatchOfPayoutTypeRequestFileEntries' => [
        // Expected to create new contact, fund account and then create payout.
        [
            'RazorpayX Account Number'  => '2224440041626905',
            'Payout Amount'             => 100,
            'Payout Currency'           => 'INR',
            'Payout Mode'               => 'NEFT',
            'Payout Purpose'            => 'refund',
            'Payout Narration'          => 'Custom narration by merchant',
            'Payout Reference Id'       => '',
            'Fund Account Id'           => '',
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567890',
            'Fund Account Vpa'          => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expected to use existing fund account and then create payout. Note
        // that contact details is different in this case then 1st but that is
        // ignored per product ask if fund account details is similar.
        [
            'RazorpayX Account Number'  => '2224440041626905',
            'Payout Amount'             => 100,
            'Payout Currency'           => 'INR',
            'Payout Mode'               => 'NEFT',
            'Payout Purpose'            => 'refund',
            'Payout Narration'          => 'Custom narration by merchant',
            'Payout Reference Id'       => '',
            'Fund Account Id'           => '',
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567890',
            'Fund Account Vpa'          => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra Ojha',
            'Contact Email'             => 'jitendra.ojha@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expect to create new fund account on existing contact and then create payout.
        [
            'RazorpayX Account Number'  => '2224440041626905',
            'Payout Amount'             => 100,
            'Payout Currency'           => 'INR',
            'Payout Mode'               => 'NEFT',
            'Payout Purpose'            => 'refund',
            'Payout Narration'          => 'Custom narration by merchant',
            'Payout Reference Id'       => '',
            'Fund Account Id'           => '',
            'Fund Account Type'         => 'bank_account',
            'Fund Account Name'         => 'Jitendra',
            'Fund Account Ifsc'         => 'SBIN0007105',
            'Fund Account Number'       => '1234567891',
            'Fund Account Vpa'          => '',
            'Contact Type'              => 'vendor',
            'Contact Name'              => 'Jitendra',
            'Contact Email'             => 'jitendra@example.com',
            'Contact Mobile'            => '9988998899',
            'Contact Reference Id'      => '',
            'notes[place]'              => 'Bangalore',
            'notes[code]'               => 'Xyz123',
        ],
        // Expect to use existing fund account and then create payout.
        [
            'RazorpayX Account Number'  => '2224440041626905',
            'Payout Amount'             => 1000,
            'Payout Currency'           => 'INR',
            'Payout Mode'               => 'NEFT',
            'Payout Purpose'            => 'refund',
            // If empty narration, must use the default, refer Payout/Entity.
            'Payout Narration'          => '',
            'Payout Reference Id'       => '',
            'Fund Account Id'           => 'fa_000000000test1',
            'Fund Account Type'         => '',
            'Fund Account Name'         => '',
            'Fund Account Ifsc'         => '',
            'Fund Account Number'       => '',
            'Fund Account Vpa'          => '',
            'Contact Type'              => '',
            'Contact Name'              => '',
            'Contact Email'             => '',
            'Contact Mobile'            => '',
            'Contact Reference Id'      => '',
            'notes[place]'              => '',
            'notes[code]'               => '',
        ],
    ],

    'testValidateFileName' => [
        'request' => [
            'url' => '/batches/validateFileName',
            'method' => 'get',
            'content' => [
                'filename' => 'file1',
                'batch_type_id' => 'tally_payout'
            ],
        ],
        'response' => [
            'content' => [
                'file_exists' => true
            ]

        ]
    ],

    'testValidateFileNameWithWrongBatchTypeId' => [
        'request' => [
            'url' => '/batches/validateFileName',
            'method' => 'get',
            'content' => [
                'filename' => 'file1',
                'batch_type_id' => 'xyz'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected batch type id is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testValidateFileNameWithoutFileNamePassed' => [
        'request' => [
            'url' => '/batches/validateFileName',
            'method' => 'get',
            'content' => [
                'filename' => '',
                'batch_type_id' => 'tally_payout'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The filename field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testValidateFileNameWithoutBatchTypePassed' => [
        'request' => [
            'url' => '/batches/validateFileName',
            'method' => 'get',
            'content' => [
                'filename' => 'file1',
                'batch_type_id' => ''
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The batch type id field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testValidateFileNameWithIncorrectMerchantAuth' => [
        'request' => [
            'url' => '/batches/validateFileName',
            'method' => 'get',
            'content' => [
                'filename' => 'file1',
                'batch_type_id' => 'tally_payout'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UNAUTHORIZED_BASICAUTH_EXPECTED,
                ],
            ],
            'status_code' => 401,
        ]
    ],

    'testSendMailFromBatchService' => [
        'request'  => [
            'url'     => '/batch/sendmail',
            'method'  => 'post',
            'content' => [
                'bucket_type'      => 'batch_service',
                'batch'            => [
                    'type'        => 'payment_link',
                    'merchant_id' => 'CVuOcOYoUiAqNY',
                ],
                'settings'         => null,
                'download_file'    => true,
                'output_file_path' => 'testing/key/payment.csv',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendSMSFromBatchService' => [
        'request'  => [
            'url'     => '/batch/sendsms',
            'method'  => 'post',
            'content' => [
                'batch'            => [
                    'type'        => 'partner_submerchant_referral_invite',
                    'merchant_id' => 'CVuOcOYoUiAqNY',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],

    'testSendSMSFromBatchServiceInvalidTemplate' => [
        'request'  => [
            'url'     => '/batch/sendsms',
            'method'  => 'post',
            'content' => [
                'batch'            => [
                    'type'        => 'random_batch',
                    'merchant_id' => 'CVuOcOYoUiAqNY',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid type: random_batch',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPayoutApprovalSendMailFromBatchService' => [
        'request'  => [
            'url'     => '/batch/sendmail',
            'method'  => 'post',
            'content' => [
                'bucket_type'      => 'batch_service',
                'batch'            => [
                    'type'        => 'payout_approval',
                    'merchant_id' => 'CVuOcOYoUiAqNY',
                ],
                'settings'         => [
                    'email' => 'blah@blah.com',
                    'user_comment' => 'some user comment'
                ],
                'download_file'    => true,
                'output_file_path' => 'testing/key/payment.csv',
            ],
        ],
        'response' => [
            'content' => [
                'success' => true,
            ],
        ],
    ],


    'testCreateAdminBatchWithRequiredPermission' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'adjustment',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'type'        => 'adjustment',
                'status'      => 'created',
                'total_count' => 1,
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateAdminBatchWithoutRequiredPermission' => [
        'request'  => [
            'url'     => '/admin/batches',
            'method'  => 'post',
            'content' => [
                'type' => 'adjustment',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_REQUIRED_PERMISSION_NOT_FOUND,
        ],
    ],

    'testPLBulkBatchCreateForValidUserRoles' => [
        'server' => [
            'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
        ],
        'request'  => [
            'url'     => '/payout-links/batch-create',
            'method'  => 'post',
            'content' => [
                'type' => 'payout_link_bulk',
                'otp'  => '0007',
                'token'=> '12345678900000',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'batch',
                'type'        => 'payout_link_bulk',
                'status'      => 'created',
                'total_count' => 1,
            ],
            'status_code' => 200,
        ],
    ],
    'testRecuringAxisChargeBatch' =>[
        'request' => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'recurring_charge_axis'
            ],
        ],
        'response' => [
            'content' => [
                'processable_count' => 1,
                'error_count'       => 0,
                'parsed_entries'    => [
                    [
                        'slno'               => '1',
                        'URNNo'              => '11223344',
                        'Folio_No'           => '91000xxxxxx',
                        'SchemeCode'         => 'AF',
                        'TransactionNo'      => '86XXX',
                        'InvestorName'       => 'Srinivas M',
                        'Purchase Day'       => '1',
                        'Pur Amount'         => '1000',
                        'BankAccountNo'      => '02951XXXXXXX',
                        'Purchase Date'      => '2/15/21',
                        'Batch Ref Number'   => '1',
                        'Branch'             => 'RPXX',
                        'Tr.Type'            => 'SIN',
                        'UMRN No / TOKEN ID' => 'HDFC60000XXXXXXXX',
                        'Credit Account No'  => '91602XXXXXX',
                    ]
                ],
            ],
        ],
    ],
    'testInvalidRecuringAxisChargeBatch' =>[
        'request' => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'recurring_charge_axis'
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
    ],

    'testPartnerSubmerchantInviteCapitalBulkCSVFileValidate' => [
        'request'  => [
            'url'     => '/batches/validate',
            'method'  => 'post',
            'content' => [
                'type' => 'partner_submerchant_invite_capital'
            ],
        ],
        'response' => [
            'content'     => [
                "processable_count" => 1,
                "error_count"       => 0,
                "parsed_entries"    => [
                    [
                        "business_name"           => "Erebor Travels",
                        "account_name"            => "Erebor Travels",
                        "contact_mobile"          => "9999999999",
                        "email"                   => "testing.capital@razorpay.com",
                        "annual_turnover_min"     => "100000",
                        "annual_turnover_max"     => "1000000",
                        "company_address_line_1"  => "Erebor Travels Pvt. Ltd.",
                        "company_address_line_2"  => "Major Industry Area",
                        "company_address_city"    => "Akola",
                        "company_address_state"   => "Maharashtra",
                        "company_address_country" => "IN",
                        "company_address_pincode" => "444001",
                        "business_type"           => "PROPRIETORSHIP",
                        "business_vintage"        => "BETWEEN_6MONTHS_12MONTHS",
                        "gstin"                   => "37ABCBS1234N1Z1",
                        "promoter_pan"            => "ABCPS1234N",
                    ],
                ],
            ],
            'status_code' => 200,
        ],
    ],
];
