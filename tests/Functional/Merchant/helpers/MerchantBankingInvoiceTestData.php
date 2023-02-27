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
    'testBankingInvoiceEntityCreateWithEInvoiceForRblCa' => [
        'rx_transactions' => [
            'amount' => 500,
            'tax'    => 90,
        ],
        'expectedContent' => [
            'access_token' => 'a78e74508f285f5cd120716b81d8e91f2af96326',
            'user_gstin' => '29AAGCR4375J1ZU',
            'transaction_details' => [
                'supply_type' => 'B2B'
            ],
            'document_details' => [
                'document_type' => 'INV',
                'document_number' => '10000000000-0721',
                'document_date' => '31/07/2021',
            ],
            'seller_details' => [
                'gstin' => '29AAGCR4375J1ZU',
                'legal_name' => 'Razorpay Software Private Limited',
                'location' => 'Bangalore',
                'pincode' => 560030,
                'state_code' => '29',
                'address1' => 'First Floor SJR Cyber 22 laskar hosur road Adugodi',
            ],
            'buyer_details' => [
                'gstin' => '29kjsngjk213922',
                'legal_name' => 'abcd',
                'location' => 'abcdef',
                'pincode' => 123456,
                'place_of_supply' => '29',
                'state_code' => '29',
                'address1' => 'abc street',
            ],
            'value_details' => [
                'total_assessable_value' => '5.00',
                'total_invoice_value' => '5.90',
                'total_igst_value' => '0.00',
                'total_sgst_value' => '0.45',
                'total_cgst_value' => '0.45',
            ],
            'item_list' => [
                [
                    'item_serial_number' => 1,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '5.00',
                    'total_amount' => '5.00',
                    'assessable_value' => '5.00',
                    'gst_rate' => 18,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '0.45',
                    'cgst_amount' => '0.45',
                    'total_item_value' => '5.90',
                    'account_type'  => 'direct',
                    'channel' => 'rbl',
                    'product_description' => 'RBL Current Account Transactions'
                ],
            ],
        ]
    ],
    'testBankingInvoiceEntityCreateWithEInvoiceForIciciCa' => [
        'rx_transactions' => [
            'amount' => 500,
            'tax'    => 90,
        ],
        'expectedContent' => [
            'access_token' => '67118f6bfaa1efedba09c90f9b2bc578e70f8468',
            'user_gstin' => '29AAKCR4702K1Z1',
            'transaction_details' => [
                'supply_type' => 'B2B'
            ],
            'document_details' => [
                'document_type' => 'INV',
                'document_number' => '10000000000-0422',
                'document_date' => '30/04/2022',
            ],
            'seller_details' => [
                'gstin' => '29AAKCR4702K1Z1',
                'legal_name' => 'RZPX PRIVATE LIMITED',
                'location' => 'Bangalore',
                'pincode' => 560030,
                'state_code' => '29',
                'address1' => 'First Floor SJR Cyber 22 laskar hosur road Adugodi',
            ],
            'buyer_details' => [
                'gstin' => '29BBYPA2999E1Z0',
                'legal_name' => 'abcd',
                'location' => 'abcdef',
                'pincode' => 560030,
                'place_of_supply' => '29',
                'state_code' => '29',
                'address1' => 'abc street',
            ],
            'value_details' => [
                'total_assessable_value' => '5.00',
                'total_invoice_value' => '5.90',
                'total_igst_value' => '0.00',
                'total_sgst_value' => '0.45',
                'total_cgst_value' => '0.45',
            ],
            'item_list' => [
                [
                    'item_serial_number' => 1,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '5.00',
                    'total_amount' => '5.00',
                    'assessable_value' => '5.00',
                    'gst_rate' => 18,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '0.45',
                    'cgst_amount' => '0.45',
                    'total_item_value' => '5.90',
                    'account_type'  => 'direct',
                    'channel' => 'icici',
                    'product_description' => 'ICICI Current Account Transactions'
                ],
            ],
        ]
    ],
    'testBankingInvoiceEntityCreateWithEInvoiceForFebruaryMonth' => [
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

    'testBankingInvoiceDownloadFromMerchantDashboardForCARbl' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/reports/invoice/banking',
            'content' => [
                "month"          => 7,
                "year"           => 2021,
            ],
        ],
        'response' => [
            'content' => [
                'file_id' => null,
                'error_message' => 'Error:PDF not generated',
            ],
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
    'testMerchantInvoicePayoutFailedOrReversedScenarios' => [
        'rx_transactions' => [
            [
                'merchant_id' => '10000000000000',
                'month'  => 8,
                'year'   => 2019,
                'amount' => 0,
                'tax'    => 0,
            ],
            [
                'merchant_id' => '10000000000000',
                'month'  => 7,
                'year'   => 2019,
                'amount' => 4000,
                'tax'    => 720,
            ],
        ],
        'rx_adjustments' => [
            [
                'merchant_id' => '10000000000000',
                'month'  => 8,
                'year'   => 2019,
                'amount' => 1500,
                'tax'    => 270,
            ],
            [
                'merchant_id' => '10000000000000',
                'month'  => 7,
                'year'   => 2019,
                'amount' => 2000,
                'tax'    => 360,
            ],
        ],
    ],
];
