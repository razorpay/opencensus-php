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
        ]
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
];
