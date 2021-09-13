<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testSettingsInternalApiAddOrUpdate'                                  => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/settings_internal/tax_payments',
            'content' => [
                'test_key' => 'test_value'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],
    'testSettingsInternalApiGet'                                          => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'url'     => '/settings_internal/tax_payments',
            'content' => []
        ],
        'response' => [
            'content' => [
                'settings' => [
                    'test_key' => 'test_value'
                ]
            ],
        ]
    ],
    'testTaxPaymentSettingGetCallsServiceMethods'                         => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPaymentSettingAddOrUpdateCallsServiceMethods'                 => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/settings',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetTaxPaymentCallsServiceMethod'                                 => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/txpy_1234',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testListTaxPaymentCallsServiceMethod'                                => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testPayTaxPaymentCallsServiceMethod'                                 => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/some_payment_id/pay',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPayContactCreationFailsWhenNotVendorPaymentApp'               => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/contacts',
            'content' => [
                'name' => 'some name',
                'type' => 'rzp_tax_pay',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ]
    ],
    'testTaxContactCreationSuccessWithTheRightVendorApp'                  => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/contacts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'name' => 'test_name',
                'type' => 'rzp_tax_pay',
            ],
        ],
        'response' => [
            'content'     => [
                'name'   => 'test_name',
                'entity' => 'contact',
                'type'   => 'rzp_tax_pay'
            ],
            'status_code' => '201',
        ]
    ],
    'testTaxPayFundAccountCreationFailsWhenNotVendorPaymentApp'           => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/fund_accounts',
            'content' => [
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name'           => 'asdsd',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_FUND_ACCOUNT_CREATION_NOT_PERMITTED,
        ]
    ],
    'testTaxFundAccountCreationSuccessWithTheRightVendorApp'              => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/fund_accounts_internal',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'account_type' => 'bank_account',
                'bank_account' => [
                    'name'           => 'test name',
                    'ifsc'           => 'ICIC0000020',
                    'account_number' => '000205031288'
                ],
            ],
        ],
        'response' => [
            'content'     => [
                'entity'       => 'fund_account',
                'contact_id'   => '',
                'account_type' => 'bank_account',
                'bank_account' => [
                    'ifsc'           => 'ICIC0000020',
                    'bank_name'      => 'ICICI Bank',
                    'name'           => 'test name',
                    'notes'          => [],
                    'account_number' => '000205031288',
                ]
            ],
            'status_code' => 201,
        ],
    ],
    'testTaxPaymentInternalContactUpdateForbidden'                        => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/contacts/',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'name' => 'new name'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INTERNAL_CONTACT_CREATE_UPDATE_NOT_PERMITTED,
        ]
    ],
    'testUpdatingContactTypeToInternalContactForbidden'                   => [
        'request'   => [
            'method'  => 'PATCH',
            'url'     => '/contacts/',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'content' => [
                'type' => 'rzp_tax_pay'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid type: rzp_tax_pay',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testPayoutCreateOnRzpInternalContactSucceeds'                        => [
        'request'  => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'entity'    => 'payout',
                'amount'    => 2000000,
                'currency'  => 'INR',
                'narration' => 'Batman',
                'purpose'   => 'refund',
                'status'    => 'processing',
                'mode'      => 'IMPS',
                'tax'       => 162,
                'fees'      => 1062,
                'notes'     => [
                    'abc' => 'xyz',
                ],
            ]
        ],
    ],
    'testPayoutInternalPayoutRouteFailsWhenFundAccountIdMissing'          => [
        'request'   => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number' => '2224440041626905',
                'amount'         => 2000000,
                'currency'       => 'INR',
                'purpose'        => 'refund',
                'narration'      => 'Batman',
                'mode'           => 'IMPS',
                'notes'          => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_FUND_ACCOUNT_ID_IS_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testPayoutInternalPayoutRouteFailsWhenContactIsNotInternalType'      => [
        'request'   => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_ONLY_INTERNAL_CONTACT_PERMITTED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testInternalPayoutFailsWhenInternalContactIsRestrictedForCurrentApp' => [
        'request'   => [
            'server'  => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method'  => 'POST',
            'url'     => '/internalContactPayout',
            'content' => [
                'account_number'  => '2224440041626905',
                'amount'          => 2000000,
                'currency'        => 'INR',
                'purpose'         => 'refund',
                'narration'       => 'Batman',
                'mode'            => 'IMPS',
                'fund_account_id' => '',
                'notes'           => [
                    'abc' => 'xyz',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => ErrorCode::BAD_REQUEST_APP_NOT_PERMITTED_TO_CREATE_PAYOUT_ON_THIS_CONTACT_TYPE,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testBulkPayTaxPaymentCallsServiceMethod'                             => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/bulk-pay',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testInternalPayoutCancelAPI'                                         => [
        'request'  => [
            'server' => [
                'HTTP_X-Razorpay-Account' => '10000000000000',
            ],
            'method' => 'POST',
            'url'    => '',
        ],
        'response' => [
            'content' => [],
        ],
    ],
    'testEnabledMerchantSettingInternalApiCall'                           => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '200DemoAccount',
            ],
            'url'     => '/tax-payments/enabledMerchantSettings',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                [
                    'merchant_id'     => '200DemoAccount',
                    'settings'        => [
                        'tax_payment_enabled'                => 'true',
                        'merchant_auto_debit_account_number' => '2224440041626905',
                    ],
                    'banking_account' => [
                        'name'           => 'yesbank',
                        'account_number' => '2224440041626905',
                        'type'           => 'current',
                        'balance'        => 200
                    ],
                ],
                [
                    'merchant_id' => '201DemoAccount',
                    'settings'    => [
                        'tax_payment_enabled'                => 'true',
                        'merchant_auto_debit_account_number' => 'm2_account'
                    ]
                ]
            ]
        ]
    ],
    'testEnabledMerchantSettingInternalApiCallWithLimit'                           => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '200DemoAccount',
            ],
            'url'     => '/tax-payments/enabledMerchantSettings?offset=0&limit=1',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                [
                    'merchant_id'     => '200DemoAccount',
                    'settings'        => [
                        'tax_payment_enabled'                => 'true',
                        'merchant_auto_debit_account_number' => '2224440041626905',
                    ],
                    'banking_account' => [
                        'name'           => 'yesbank',
                        'account_number' => '2224440041626905',
                        'type'           => 'current',
                        'balance'        => 200
                    ],
                ]
            ]
        ]
    ],
    'testEnabledMerchantSettingInternalApiCallWithOffset'                           => [
        'request'  => [
            'method'  => 'GET',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '200DemoAccount',
            ],
            'url'     => '/tax-payments/enabledMerchantSettings?offset=1&limit=1',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                [
                    'merchant_id' => '201DemoAccount',
                    'settings'    => [
                        'tax_payment_enabled'                => 'true',
                        'merchant_auto_debit_account_number' => 'm2_account'
                    ]
                ]
            ]
        ]
    ],
    'createTestSettingsForMerchant'                                       => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Razorpay-Account' => '200DemoAccount',
            ],
            'url'     => '/settings_internal/tax_payments',
            'content' => [
                'test_key' => 'test_value'
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ]
        ]
    ],
    'testInitiateMonthlyPayoutsCallsServiceMethod'                        => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/initiateMonthlyPayouts',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testUpcomingEmailCronCallsServiceMethod'                             => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/mailCron',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testQueuedPayoutCronAPICallsServiceMethod'                           => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/cancelQueuedPayouts',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testSendMailServiceMethodIsCalled'                                   => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/sendMail',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testMonthlySummaryServiceMethodIsCalled'                             => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/_meta/summary',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testSendEmailValidation'                                             => [
        'request'   => [
            'method' => 'POST',
            'url'    => '/tax-payments/sendMail',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The merchant email field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testSendEmailDataFieldRequired'                                      => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/tax-payments/sendMail',
            'content' => [
                'merchant_email' => 'some@mail.com'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The data field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testSendEmailSubjectFieldRequired'                                   => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/tax-payments/sendMail',
            'content' => [
                'merchant_email' => 'some@mail.com',
                'data'           => ['some' => 'data'],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The subject field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],
    'testSendEmailTemplateFieldRequired'                                  => [
        'request'   => [
            'method'  => 'POST',
            'url'     => '/tax-payments/sendMail',
            'content' => [
                'merchant_email' => 'some@mail.com',
                'data'           => ['some' => 'data'],
                'subject'        => 'some subject'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The template name field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],
    'testPayoutWithTaxPaymentPurposeCanBeDeleted'                         => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/payouts/<>/cancel',
        ],
        'response' => [
            'content' => [
                'status'  => 'cancelled',
                'purpose' => 'rzp_tax_pay',
            ]
        ]
    ],
    'testTaxPaymentMarkAsPaid'                                            => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/tax-payments/mark-as-paid',
            'content' => [
                'tax_payment_id'         => ['txpy_F2qwMZe97QTGG1'],
                'manually_paid_metadata' => [
                    'notes1' => 'smoething'
                ],
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPaymentUploadChallan'                                         => [
        'request'  => [
            'method'  => 'POST',
            'server'  => [
                'HTTP_X-Dashboard-User-Id' => '20000000000000',
            ],
            'url'     => '/tax-payments/upload-challan',
            'content' => [
            ],
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPaymentEditTp'                                                => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/txpy_1234/edit',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testbulkChallanDownload'                                                => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/challans/download',
        ],
        'response' => [
            'content' => [
                'zip_file_id' => "file_HjPPzIGMCbahkO"
            ]
        ]
    ],
    'testTaxPaymentMarkAsPaidNegative'                                    => [
        'request'   => [
            'method'  => 'POST',
            'server'  => [
            ],
            'url'     => '/tax-payments/mark-as-paid',
            'content' => [
                'tax_payment_id'         => ['txpy_F2qwMZe97QTGG1'],
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
    'testGetInternalMerchantWhenNoSettingsPresent'                                             => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000',
        ],
        'response' => [
            'content' => [
                'support_details' => [
                    'support_contact' => null,
                    'support_email'   => null,
                    'support_url'     => null,
                    'contrast_color'  => '#FFFFFF'
                ],
            ]
        ]
    ],
    'testGetInternalMerchantWhenSettingsArePresent'                                             => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/internal/merchants/10000000000000',
        ],
        'response' => [
            'content' => [
                'support_details' => [
                    'support_contact' => '1234',
                    'support_email'   => 'test@email.com',
                    'support_url'     => 'test.com',
                    'contrast_color'  => '#FFFFFF'
                ],
            ]
        ]
    ],
    'testTaxPaymentAddPenaltyCronCallsServiceMethod'                      => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/tax-payments/addPenalty',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [

            ]
        ]
    ],
    'testTaxPaymentCreateTPCallsServiceMethod'                         => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPaymentEditTPCallsServiceMethod'                         => [
        'request'  => [
            'method' => 'PATCH',
            'url'    => '/tax-payments/txpy_1234',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testTaxPaymentCancelTPCallsServiceMethod'                         => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/txpy_1234/cancel',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testCreateDirectTaxPaymentCallsServiceMethod'                         => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/direct',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetTdsCategoriesCallsServiceMethod'                         => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/direct/tds-categories',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testWebHookHandlerCallsServiceMethod'                         => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/direct/pg-webhook',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetInvalidTanStatus'                         => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/_meta/invalid_tan_status',
        ],
        'response' => [
            'content' => []
        ]
    ],
    'testGetDowntimeSchedule'                        => [
        'request'  => [
            'method' => 'GET',
            'url'    => '/tax-payments/_meta/downtime_schedule',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testReminderCallback'                        => [
        'request'  => [
            'method' => 'POST',
            'url'    => '/tax-payments/reminders/live/tax_payments/txpy_1234',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testFetchPendingGstCallsServiceMethod' => [
        'request' => [
            'method' => 'GET',
            'url' => '/tax-payments/gst/fetch',
        ],
        'response' => [
            'content' => []
        ]
    ],
];
