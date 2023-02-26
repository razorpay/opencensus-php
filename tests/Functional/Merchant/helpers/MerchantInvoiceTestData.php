<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use RZP\Models\FundAccount\Entity as FundAccount;
use RZP\Models\BankAccount\Entity as BankAccount;
use RZP\Models\FundAccount\Validation\Entity as Validation;

return [

    'testInvoiceEntityCreateForPrevMonth' => [
        'others'      => [
            'amount'    => 1510,
            'tax'       => 272,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'validation'    => [
            'amount'    => 300,
            'tax'       => 54,
            'gstin'     => '29kjsngjk213922',
        ],
        'instant_refunds' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'pricing_bundle' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ]
    ],

    'testMerchantInvoiceWithLateAuth' => [
        'others'      => [
            'amount'    => 1510,
            'tax'       => 272,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'validation'    => [
            'amount'    => 300,
            'tax'       => 54,
            'gstin'     => '29kjsngjk213922',
        ],
        'instant_refunds' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'pricing_bundle' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ]
    ],

    'testInvoiceEntityCreateForGivenMonthYear' => [
        'others'      => [
            'amount'    => 1510,
            'tax'       => 272,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'validation'    => [
            'amount'    => 300,
            'tax'       => 54,
            'gstin'     => '29kjsngjk213922',
        ],
        'instant_refunds' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'pricing_bundle' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
    ],

    'testFeeAdjustment' => [
        'merchant_id'   => '10000000000000',
        'gstin'         => '29kjsngjk213922',
        'type'          => 'adjustment',
        'amount'        => -1300,
        'tax'           => 123,
    ],

    'testFeeAdjustmentFailure'  => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testInvoiceEntityCreateForGivenMerchant' => [
        'others'      => [
            'amount'    => 1510,
            'tax'       => 272,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'validation'    => [
            'amount'    => 300,
            'tax'       => 54,
            'gstin'     => '29kjsngjk213922',
        ],
        'instant_refunds' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'pricing_bundle' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ]
    ],

    'testEditGstinFailure' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Invoice Number.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_MERCHANT_INVOICE_NUMBER,
        ],
    ],

    'createValidationWithFundAccountEntity' => [
        'request' => [
            'url'     => '/fund_accounts/validations',
            'method'  => 'post',
            'content' => [
                Validation::FUND_ACCOUNT  => [
                    FundAccount::ACCOUNT_TYPE => 'bank_account',
                    FundAccount::DETAILS      => [
                        BankAccount::ACCOUNT_NUMBER => '123456789',
                        BankAccount::NAME           => 'Rohit Keshwani',
                        BankAccount::IFSC           => 'SBIN0010411',
                    ],
                ],
                Validation::AMOUNT        => '100',
                Validation::CURRENCY      => 'INR',
                Validation::NOTES         => [],
                Validation::RECEIPT       => '12345667',
            ],
        ],
        'response' => [
            'content' => [
                'entity'       => 'fund_account.validation',
                'fund_account' => [
                    'entity'       => 'fund_account',
                    'account_type' => 'bank_account',
                    'active'       => true,
                    'details'      => [
                        'account_number' => '123456789',
                        'name'           => 'Rohit Keshwani',
                        'ifsc'           => 'SBIN0010411',
                        'bank_name'      => 'State Bank of India',
                    ],
                ],
                'status'       => 'created',
                'amount'       => 100,
                'currency'     => 'INR',
                'notes'        => [],
                'results'      => [
                    'account_status'  => null,
                    'registered_name' => null,
                ],
            ],
        ],
    ],

   'testInstantRefundsInvoiceEntityCreateForGivenMerchant' => [
        'others'      => [
            'amount'    => 1510,
            'tax'       => 272,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'validation'    => [
            'amount'    => 300,
            'tax'       => 54,
            'gstin'     => '29kjsngjk213922',
        ],
        'instant_refunds' => [
            'amount'    => 100,
            'tax'       => 18,
            'gstin'     => '29kjsngjk213922',
        ],
       'pricing_bundle' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ]
    ],

    'testMerchantInvoiceSkippedListEdit' => [
        'others'      => [
            'amount'    => 1510,
            'tax'       => 272,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_gt_2k'    => [
            'amount'    => 4680,
            'tax'       => 842,
            'gstin'     => '29kjsngjk213922',
        ],
        'card_lte_2k'    => [
            'amount'    => 1000,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ],
        'validation'    => [
            'amount'    => 300,
            'tax'       => 54,
            'gstin'     => '29kjsngjk213922',
        ],
        'instant_refunds' => [
            'amount'    => 0,
            'tax'       => 0,
            'gstin'     => '29kjsngjk213922',
        ]
    ],

    'testPgInvoiceEntityCreateWithEInvoice' => [
        'INV' => [
            'access_token' => 'a78e74508f285f5cd120716b81d8e91f2af96326',
            'user_gstin' => '29AAGCR4375J1ZU',
            'transaction_details' => [
                'supply_type' => 'B2B'
            ],
            'document_details' => [
                'document_type' => 'INV',
                'document_number' => 'hello12345670721',
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
                'address1' => 'FILM CENTRE BUILDING, MUMBAI, 68, TARDEO ROAD, 2B ii, Mumbai City, Maharashtra, GROUND FLOOR, 400034',
            ],
            'value_details' => [
                'total_assessable_value' => '74.90',
                'total_invoice_value' => '86.58',
                'total_igst_value' => '0.00',
                'total_sgst_value' => '5.84',
                'total_cgst_value' => '5.84',
            ],
            'item_list' => [
                [
                    'product_description' => 'Commission on Card Payments less than equal to INR 2,000',
                    'item_serial_number' => 1,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '10.00',
                    'total_amount' => '10.00',
                    'assessable_value' => '10.00',
                    'gst_rate' => 0,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '0.00',
                    'cgst_amount' => '0.00',
                    'total_item_value' => '10.00',
                ],
                [
                    'product_description' => 'Commission on Card Payments greater than INR 2,000',
                    'item_serial_number' => 2,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '46.80',
                    'total_amount' => '46.80',
                    'assessable_value' => '46.80',
                    'gst_rate' => 18,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '4.21',
                    'cgst_amount' => '4.21',
                    'total_item_value' => '55.22',
                ],
                [
                    'product_description' => 'Commission on All Methods Except Cards',
                    'item_serial_number' => 3,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '15.10',
                    'total_amount' => '15.10',
                    'assessable_value' => '15.10',
                    'gst_rate' => 18,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '1.36',
                    'cgst_amount' => '1.36',
                    'total_item_value' => '17.82',
                ],
                [
                    'product_description' => 'Commission on All Validations',
                    'item_serial_number' => 4,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '3.00',
                    'total_amount' => '3.00',
                    'assessable_value' => '3.00',
                    'gst_rate' => 18,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '0.27',
                    'cgst_amount' => '0.27',
                    'total_item_value' => '3.54',
                ],
            ],
        ],
        'CRN' => [
            'access_token' => 'a78e74508f285f5cd120716b81d8e91f2af96326',
            'user_gstin' => '29AAGCR4375J1ZU',
            'transaction_details' => [
                'supply_type' => 'B2B'
            ],
            'document_details' => [
                'document_type' => 'CRN',
                'document_number' => 'hello12345670721',
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
                'address1' => 'FILM CENTRE BUILDING, MUMBAI, 68, TARDEO ROAD, 2B ii, Mumbai City, Maharashtra, GROUND FLOOR, 400034',
            ],
            'value_details' => [
                'total_assessable_value' => '13.00',
                'total_invoice_value' => '13.00',
                'total_igst_value' => '0.00',
                'total_sgst_value' => '0.00',
                'total_cgst_value' => '0.00',
            ],
            'item_list' => [
                [
                    'product_description' => 'Fee adjustment',
                    'item_serial_number' => 1,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '13.00',
                    'total_amount' => '13.00',
                    'assessable_value' => '13.00',
                    'gst_rate' => 0,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '0.00',
                    'cgst_amount' => '0.00',
                    'total_item_value' => '13.00',
                ],
            ],
        ],
        'DBN' => [
            'access_token' => 'a78e74508f285f5cd120716b81d8e91f2af96326',
            'user_gstin' => '29AAGCR4375J1ZU',
            'transaction_details' => [
                'supply_type' => 'B2B'
            ],
            'document_details' => [
                'document_type' => 'DBN',
                'document_number' => 'hello12345670721',
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
                'address1' => 'FILM CENTRE BUILDING, MUMBAI, 68, TARDEO ROAD, 2B ii, Mumbai City, Maharashtra, GROUND FLOOR, 400034',
            ],
            'value_details' => [
                'total_assessable_value' => '13.00',
                'total_invoice_value' => '13.00',
                'total_igst_value' => '0.00',
                'total_sgst_value' => '0.00',
                'total_cgst_value' => '0.00',
            ],
            'item_list' => [
                [
                    'product_description' => 'Fee adjustment',
                    'item_serial_number' => 1,
                    'is_service' => 'Y',
                    'hsn_code' => '997158',
                    'unit' => 'OTH',
                    'quantity' => 1,
                    'unit_price' => '13.00',
                    'total_amount' => '13.00',
                    'assessable_value' => '13.00',
                    'gst_rate' => 0,
                    'igst_amount' => '0.00',
                    'sgst_amount' => '0.00',
                    'cgst_amount' => '0.00',
                    'total_item_value' => '13.00',
                ],
            ],
        ],
    ],
];
