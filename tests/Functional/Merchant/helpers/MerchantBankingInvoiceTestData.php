<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testBankingInvoiceEntityCreateForGivenMonthYear' => [
        'rx_transactions' => [
            'amount' => 500,
            'tax'    => 90,
        ],
    ],
    'testBankingInvoiceEntityCreateWithEInvoice' => [
        'rx_transactions' => [
            'amount' => 500,
            'tax'    => 90,
        ],
    ],
    'testBankingInvoiceEntityCreateWithEInvoiceForZeroAmountLineItem' => [
        'rx_transactions' => [
            'amount' => 0,
            'tax'    => 0,
        ],
    ],
    'testBankingInvoiceEntityCreateWithEInvoiceForNegativeAmountLineItem' => [
        'rx_transactions' => [
            'amount' => 0,
            'tax'    => 0,
        ],
    ],
    'testBankingInvoiceEntityCreateWithEInvoiceForNegativeAndPositiveAmountLineItem' => [
        'rx_transactions' => [
            [
                'month'  => 8,
                'year'   => 2021,
                'amount' => 900,
                'tax'    => 162,
            ],
            [
                'month'  => 8,
                'year'   => 2021,
                'amount' => -900,
                'tax'    => -162,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYear'  => [
        'rx_transactions' => [
            [
                "amount" => 900,
                "tax"    => 162,
            ],
            [
                "amount" => 500,
                "tax"    => 90,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearForMultipleMerchants' => [
        'rx_transactions' => [
            [
                'merchant_id' => "100000Razorpay",
                'amount'      => 500,
                'tax'         => 90,
            ],
            [
                'merchant_id' => "10000000000000",
                'amount'      => 900,
                'tax'         => 162,
            ],
            [
                'merchant_id' => "10000000000000",
                'amount'      => 500,
                'tax'         => 90,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearForGivenMerchantWithNoBankingTransaction'=>[
        'rx_transactions' => [
            [
                "amount" => 0,
                "tax"    => 0,
            ],
            [
                "amount" => 0,
                "tax"    => 0,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsForGivenMonthYearWithPayoutReversed' => [
        'rx_transactions' => [
            [
                'type'   => "rx_transactions",
                'amount' => 500,
                'tax'    => 90,
            ],
            [
                'type'   => "rx_transactions",
                'amount' => 500,
                'tax'    => 90,
            ],
        ],
        'rx_adjustments' => [
            [
                'type'   => "rx_adjustments",
                'amount' => 500,
                'tax'    => 90,
            ],
            [
                'type'   => "rx_adjustments",
                'amount' => 0,
                'tax'    => 0,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsWithPayoutReversalInNextMonthAndNoPayoutsNextMonth' => [
        'rx_transactions' => [
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 900,
                'tax'    => 162,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 500,
                'tax'    => 90,
            ],
        ],
        'rx_adjustments' => [
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 900,
                'tax'    => 162,
            ],
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
        ],
    ],
    'testBankingInvoiceEntityCreateForMultipleAccountsWithPayoutReversalInNextMonthAndSomePayoutsNextMonthFromAnotherBankingBalance' => [
        'rx_transactions' => [
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 500,
                'tax'    => 90,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 900,
                'tax'    => 162,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 500,
                'tax'    => 90,
            ],
        ],
        'rx_adjustments' => [
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 900,
                'tax'    => 162,
            ],
            [
                'month'  => 8,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'month'  => 7,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
        ],
    ],
    'testFetchMultipleBankingInvoices' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [
                'month'       => 7,
                'year'        => 2019,
                ],
            ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' =>  [
                    [
                        'month'          => 7,
                        'year'           => 2019,
                        'amount'         => 1400,
                        'tax'            => 252,
                    ],
                ],
            ],
        ],
    ],
    'testFetchMultipleBankingInvoicesWhenMonthIsGivenWithoutYear' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [
                'month'       => 7,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only month not allowed . Year should be sent with month or only year can be sent',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testFetchMultipleBankingInvoicesGivenNoInputs' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 2,
                'items'  => [
                    [
                        'month'          => 8,
                        'year'           => 2019,
                        'amount'         => 0,
                        'tax'            => 0,
                    ],
                    [
                        'month'          => 7,
                        'year'           => 2019,
                        'amount'         => 1400,
                        'tax'            => 252,
                    ],
                ],
            ],
        ],
    ],
    'testFetchMultipleBankingInvoicesGivenNoInputsAndNoBankingInvoiceGeneratedYet' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count'  => 0,
                'items'  => [],
            ],
        ],
    ],

    'testFetchMultipleBankingInvoicesWithBusinessBankingNotEnabled' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/merchants/banking/invoices',
            'content' => [
                'month' => 7,
                'year'  => 2019,
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_FORBIDDEN_BUSINESS_BANKING_NOT_ENABLED,
        ],
    ],

    'testBankingInvoiceDownloadFromAdminDashboard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/reports/invoice/banking',
            'content' => [
                "month"          => 7,
                "year"           => 2019,
                "send_email"     => false,
                "to_emails"      => ["kunal.sikri@razorpay.com"]
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'file_id' => 'file_1cXSLlUU8V9sXl'
            ],
        ],
    ],

    'testFetchMerchantInvoiceWithoutPermissionFromAdminDashboard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/reports/invoice/banking',
            'content' => [
                "month"          => 7,
                "year"           => 2019,
                "send_email"     => false,
                "to_emails"      => ["kunal.sikri@razorpay.com"]
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ACCESS_DENIED
        ]
    ],

    'testBankingInvoiceWithFailedPayoutsInGivenMonthAndYear' => [
        'rx_transactions' => [
            'amount' => 2009,
            'tax'    => 362,
        ],
        'rx_adjustments' => [
            'amount' => 500,
            'tax'    => 90,
        ],
    ],

    'testBankingInvoiceWithFailedPayoutsInGivenMonthButInitiatedPreviousMonthAndNoPayoutsInGivenMonth' => [
        'rx_transactions' => [
            'amount' => 0,
            'tax'    => 0,
        ],
        'rx_adjustments' => [
            'amount' => 500,
            'tax'    => 90,
        ],
    ],

    'testMerchantInvoiceFetchFromAdminDashboardWhenMonthIsGivenWithYearAndMerchantId' => [
        'rx_transactions' => [
            'month'       => 7,
            'year'        => 2019,
            'merchant_id' => '10000000000000',
            'amount'      => 500,
            'tax'         => 90,
        ],
    ],

    'testMerchantInvoiceFetchFromAdminDashboardWhenMonthIsGivenWithoutYearButWithMerchantId' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/merchant_invoice',
            'content' => [
                'month'       => 7,
                'merchant_id' => '10000000000000',
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Year and merchant_id should be sent with month or only year can be sent with merchant_id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],
    'testBankingInvoiceEmailFromAdminDashboard' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/admin/reports/invoice/banking',
            'content' => [
                "month"          => 7,
                "year"           => 2019,
                "send_email"     => true,
                "to_emails"      => ["kunal.sikri@razorpay.com"]
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'file_id' => null
            ],
        ],
    ],

    'testMerchantInvoiceFetchFromAdminDashboardWhenMonthIsGivenWithoutYearAndMerchantId' => [
        'request'  => [
            'method'  => 'GET',
            'url'     => '/admin/merchant_invoice',
            'content' => [
                'month' => 7,
            ],
            'server' => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Year and merchant_id should be sent with month or only year can be sent with merchant_id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testMerchantInvoiceFetchFromAdminDashboardWhenYearIsGivenWithMerchantId' => [
        'rx_transactions' => [
            'year'        => 2019,
            'merchant_id' => '10000000000000',
            'amount'      => 500,
            'tax'         => 90,
        ],
    ],

    'testMerchantInvoiceFetchFromAdminDashboardWhenYearIsGivenWithoutMerchantId' => [
        'request'   => [
            'method'  => 'GET',
            'url'     => '/admin/merchant_invoice',
            'content' => [
                'year' => 2019,
            ],
            'server'  => [
                'HTTP_X_RAZORPAY_ACCOUNT' => '10000000000000',
            ]
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Only year not allowed . Year should be sent with merchant_id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],
];
