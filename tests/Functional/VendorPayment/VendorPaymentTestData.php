<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCompositeExpands' => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/vendor-payments/composite-expands',
            'content' => [
                'user_ids'         => ['10000000000000'],
                'fund_account_ids' => ['fa_D6Z9Jfir2egAUD'],
                'contact_ids'      => ['cont_Dsp92d4N1Mmm6Q'],
                'payout_ids'       => ['pout_DuuYxmO7Yegu3x'],
                'merchant_ids'     => ['10000000000000'],
            ],
        ],
        'response' => [
            'content' => [
                'merchants'     => [],
                'users'         => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'id'   => '10000000000000',
                            'name' => 'test-me'
                        ]
                    ]
                ],
                'fund_accounts' => [
                    'fa_D6Z9Jfir2egAUT' => [
                        'id'           => 'fa_D6Z9Jfir2egAUT',
                        'account_type' => 'bank_account'
                    ],
                    'fa_D6Z9Jfir2egAUD' => [
                        'id'           => 'fa_D6Z9Jfir2egAUD',
                        'account_type' => 'bank_account'
                    ]
                ],
                'contacts'      => [
                    'cont_Dsp92d4N1Mmm6Q' => [
                        'id'   => 'cont_Dsp92d4N1Mmm6Q',
                        'name' => 'test_contact'
                    ]
                ],
                'payouts'       => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'id'              => 'pout_DuuYxmO7Yegu3x',
                            'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                            'fund_account'    => [
                                'id'      => 'fa_D6Z9Jfir2egAUT',
                                'contact' => [
                                    'id'   => 'cont_Dsp92d4N1Mmm6Q',
                                    'name' => 'test_contact'
                                ]
                            ]
                        ]
                    ]
                ]
            ]
        ]

    ],

    'testCompositeExpandsWhenOnlyPayoutIsPassed' => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin'   => config('applications.banking_service_url'),
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/vendor-payments/composite-expands',
            'content' => [
                'payout_ids'   => ['pout_DuuYxmO7Yegu3x'],
                'merchant_ids' => ['10000000000000']
            ],
        ],
        'response' => [
            'content' => [
                'merchants'     => [],
                'fund_accounts' => [
                    'fa_D6Z9Jfir2egAUT' => [
                        'id'           => 'fa_D6Z9Jfir2egAUT',
                        'account_type' => 'bank_account'
                    ]
                ],
                'contacts'      => [
                    'cont_Dsp92d4N1Mmm6Q' => [
                        'id'   => 'cont_Dsp92d4N1Mmm6Q',
                        'name' => 'test_contact'
                    ]
                ],
                'payouts'       => [
                    'entity' => 'collection',
                    'count'  => 1,
                    'items'  => [
                        [
                            'id'              => 'pout_DuuYxmO7Yegu3x',
                            'fund_account_id' => 'fa_D6Z9Jfir2egAUT',
                            'fund_account'    => [
                                'id'      => 'fa_D6Z9Jfir2egAUT',
                                'contact' => [
                                    'id'   => 'cont_Dsp92d4N1Mmm6Q',
                                    'name' => 'test_contact'
                                ]
                            ]
                        ]
                    ]
                ]


            ]
        ]

    ],

    'testVendorPaymentBulkCancel' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/bulk-cancel',
            'content' => [
                'vendor_payments_ids' => ['vdpm_F2qwMZe97QTGG1'],
                'cancellation_reason' => 'some reson'
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVendorPaymentGetOcrData' => [
        'request'  => [
            'method' => 'GET',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'    => '/vendor-payments/get-ocr-data/ocr_1234556',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVendorPaymentOcrAccuracyCheck' => [
        'request'  => [
            'method' => 'POST',
            'server' => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'    => '/vendor-payments/_meta/ocr-accuracy-check',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVendorPaymentMarkAsPaid' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/mark-as-paid',
            'content' => [
                'vendor_payment_id'      => ['vdpm_F2qwMZe97QTGG1'],
                'manually_paid_metadata' => [
                    'notes1' => 'smoething'
                ],
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testVendorPaymentMarkAsPaidNegative' => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
            'url'     => '/vendor-payments/mark-as-paid',
            'content' => [
                'vendor_payment_id'      => ['vdpm_F2qwMZe97QTGG1'],
                'manually_paid_metadata' => [
                    'notes1' => 'smoething'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_USER_ID_HEADER_MISSING_FROM_REQUEST,
        ]
    ],

    'testGetReportingInfo' => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/_meta/get-reporting-info',
            'content' => [
                'status'      => 'unpaid',
                'payout_mode' => 'IMPS',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetReportingInfoFromCARole' => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000006',
            ],
            'url'     => '/vendor-payments/_meta/get-reporting-info',
            'content' => [
                'status'      => 'unpaid',
                'payout_mode' => 'IMPS',
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testBulkInvoiceDownload' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/_meta/bulk-invoice-download',
            'content' => [
                'file_ids'   => ['id1', 'id2'],
                'send_email' => false,
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testEditInvoice' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/vp_id/update-invoice-file-id',
            'content' => [
                'invoice_file_id' => 'id1'
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testEditInvoiceFromCARole' => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000006',
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'url'     => '/vendor-payments/vp_id/update-invoice-file-id',
            'content' => [
                'invoice_file_id' => 'id1'
            ],
        ],
        'response' => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTHENTICATION_FAILED,
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testGetUfhFileStatus' => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/invoices/ufh/file_12345',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testCreatePayout'                                     => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'processing',
                'mode'            => 'IMPS',
                'tax'             => 162,
                'fees'            => 1062,
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
    ],
    'testCreateScheduledPayout'                            => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'     => config('applications.banking_service_url'),
                'HTTP_X-Razorpay-Account'   => '10000000000000',
                'HTTP_X-Payout-Idempotency' => 'test_i_key',
            ],
            'url'     => '/payouts_internal',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => 'fa_100000000000fa',
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'scheduled_at'    => 123,
                'source_details'  => [
                    '0' => [
                        'source_id'   => '123',
                        'source_type' => 'vendor_payments',
                        'priority'    => 1
                    ]
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'          => 'payout',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'fund_account_id' => 'fa_100000000000fa',
                'narration'       => 'Batman',
                'purpose'         => 'refund',
                'status'          => 'scheduled',
                'mode'            => 'IMPS',
                'tax'             => 0,
                'fees'            => 0,
                'notes'           => [
                    'abc' => 'xyz',
                ],
                'scheduled_at'    => 123,
                'source_details'  => [
                    '0' => [
                        'source_id'   => '123',
                        'source_type' => 'vendor_payments',
                        'priority'    => 1
                    ]
                ],
            ],
        ],
    ],
    'testVendorPaymentGenericEmailRouteCallsServiceMethod' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/sendMailGeneric',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testUpcomingMailCronRouteCallsServiceMethod'          => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/sendUpcomingMailCron',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testVendorPaymentSendMailValidation'                  => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/sendMailGeneric',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The to emails field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testVendorPaymentSendMailValidateEmails'              => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/sendMailGeneric',
            'content' => [
                'to_emails'     => ['wrongmail'],
                'data'          => ['some data'],
                'subject'       => 'some subject',
                'template_name' => 'some template',
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The to_emails.0 must be a valid email address.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testVendorPaymentBulkExecuteCallsServiceMethods'      => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/bulk/execute',
            'content' => [],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetQuickFilterAmounts'                            => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/_meta/quick-filter-amounts',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testProcessIncomingMail'                              => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/mailgun-webhook',
            'content' => [
                'sender'    => 'abc@abc.com',
                'recipient' => 'invoices+anything@invoices.razorpay.com',
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content'     => [
                'error' => 'error'
            ],
            'status_code' => 406
        ]
    ],
    'testProcessIncomingMailWithoutStatusCode'             => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/mailgun-webhook',
            'content' => [
                'sender'    => 'abc@abc.com',
                'recipient' => 'invoices+anything@invoices.razorpay.com',
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content'     => [
                'error' => 'error'
            ],
            'status_code' => 400
        ]
    ],
    'testProcessIncomingMailSuccess'                       => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/vendor-payments/mailgun-webhook',
            'content' => [
                'sender'    => 'abc@abc.com',
                'recipient' => 'invoices+anything@invoices.razorpay.com',
            ],
            'server'  => [
                'HTTP_X-Request-Origin' => config('applications.banking_service_url'),
            ],
        ],
        'response' => [
            'content'     => [
                'mail' => 'mail_something'
            ],
            'status_code' => 200
        ]
    ],
    'testGetMerchantEmailAddress'                          => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/email-integration/email',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testCreateMerchantEmailMapping'                       => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/vendor-payments/email-integration/email',
            'content' => [],
        ],
        'response' => [
            'status_code' => 200,
            'content'     => [
                'email_address' => 'invoices+abcdef@invoice.razorpay.com'
            ]
        ]
    ],
    'testSendVendorInvite' => [
        'request'  => [
            'method' => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000006',
                'HTTP_X-Request-Origin'    => config('applications.banking_service_url'),
            ],
            'url'    => '/vendor-payments/invite-vendor',
            'content' => [],
        ],
        'response' => [
            'status_code' => 200,
            'content' => [
                'success' => true
            ]
        ]
    ]
];
