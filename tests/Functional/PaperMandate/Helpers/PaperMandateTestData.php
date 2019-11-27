<?php

namespace RZP\Tests\Functional\PaperMandate;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateAuthLinkForPaperMandate' => [
        'request' => [
            'content' => [
                'amount' => 0,
                'currency' => 'INR',
                'method' => 'nach',
                'receipt' => 'rcptid #1',
                'payment_capture' => 1,
                'customer_id' => 'cust_100000customer',
                'token' =>
                [
                    'first_payment_amount' => '5000',
                    'max_amount' => '500000',
                    'expire_at' => '2047483647',
                    'nach' => [
                        'create_form' => true,
                        'form_reference1' => 'ttt',
                        'form_reference2' => 'qqq',
                    ],
                    'bank_account' =>
                    [
                        'account_number' => '1111111111111',
                        'ifsc_code' => 'HDFC0001233',
                        'beneficiary_name' => 'Gaurav Kumar',
                        'beneficiary_mobile' => '9483159238'
                    ]
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid #1',
                'token'          =>   [
                    'method'        => 'nach',
                    'nach' => [
                        'create_form'     => true,
                        'form_reference1' => 'ttt',
                        'form_reference2' => 'qqq',
                    ],
                ],
            ],
        ],
    ],

    'testCreateAuthLinkForPaperMandateWithoutBankAccountForMandate' => [
        'request' => [
            'content' => [
                'amount' => 0,
                'currency' => 'INR',
                'method' => 'nach',
                'receipt' => 'rcptid #1',
                'payment_capture' => 1,
                'customer_id' => 'cust_100000customer',
                'token' =>
                    [
                        'first_payment_amount' => '500000',
                        'max_amount' => '500',
                        'expire_at' => '2047483647',
                        'nach' => [
                            'create_form'     => true,
                            'form_reference1' => 'ttt',
                            'form_reference2' => 'qqq',
                        ],
                    ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The bank account field is required when method is nach.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateAuthLinkForPaperMandateWithoutPaperMandateField' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'nach',
                'bank'           => 'UTIB',
                'customer_id'    => 'cust_100000customer',
                'token'          => [
                    "max_amount" => 1000,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The paper mandate field is required when method is nach.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testAuthenticatePaperMandate' => [
        'request' => [
            'content' => [
                'auth_link_id' => 'inv_1000000invoice',
            ],
            'method'    => 'POST',
            'url'       => '/token.registration/paper_mandate/authenticate',
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],

    'testAuthenticatePaperMandateWithoutCustomerSign' => [
        'request' => [
            'content' => [
                'auth_link_id' => 'inv_1000000invoice',
            ],
            'method'    => 'POST',
            'url'       => '/token.registration/paper_mandate/authenticate',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'signature is not detected in the NACH form',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testAuthenticatePaperMandateWithWrongAccountNumber' => [
        'request' => [
            'content' => [
                'auth_link_id' => 'inv_1000000invoice',
            ],
            'method'    => 'POST',
            'url'       => '/token.registration/paper_mandate/authenticate',
        ],
        'response'  => [
            'content'     => [
                'success' => false,
                'errors'  => [
                    'not_matching' => [
                        'bank_account.account_number',
                    ],
                ]
            ],
        ]
    ],

    'testAuthenticatePaperMandateWithTertiarySignaturePresentWithoutSecondary' => [
        'request' => [
            'content' => [
                'auth_link_id' => 'inv_1000000invoice',
            ],
            'method'    => 'POST',
            'url'       => '/token.registration/paper_mandate/authenticate',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'tertiary signature can\'t be present without secondary signature',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'hyperVergeExtractNACHOutput' => array (
        'type' => 'nach',
        'details' =>
            array (
                'emailId' =>
                    array (
                        'to-be-reviewed' => 'yes',
                        'value' => 'gaurav.kumar12@example.com',
                        'conf' => 47,
                    ),
                'amountInWords' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'TEN',
                        'conf' => 99,
                    ),
                'utilityCode' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'NACH00000000013149',
                        'conf' => 100,
                    ),
                'reference1' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '121211212112121121',
                        'conf' => 100,
                    ),
                'bankName' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'HDFC BANK',
                        'conf' => 100,
                    ),
                'debitType' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'maximumAmount',
                        'conf' => 100,
                    ),
                'MICR' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '',
                        'conf' => 98,
                    ),
                'frequency' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'yearly',
                        'conf' => 100,
                    ),
                'signaturePresentTertiary' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'no',
                        'conf' => 100,
                    ),
                'untilCanceled' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'true',
                        'conf' => 100,
                    ),
                'signaturePresentSecondary' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'no',
                        'conf' => 100,
                    ),
                'NACHType' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'create',
                        'conf' => 100,
                    ),
                'accountNumber' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '1111111111111',
                        'conf' => 98,
                    ),
                'nachDate' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '19/08/2019',
                        'conf' => 100,
                    ),
                'phoneNumber' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '9123456780',
                        'conf' => 100,
                    ),
                'tertiaryAccountHolder' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'THE DON',
                        'conf' => 100,
                    ),
                'UMRN' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '',
                        'conf' => 100,
                    ),
                'companyName' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'TEST',
                        'conf' => 100,
                    ),
                'IFSCCode' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'RZPB0000000',
                        'conf' => 100,
                    ),
                'reference2' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '121211212112121121',
                        'conf' => 100,
                    ),
                'accountType' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'SB',
                        'conf' => 100,
                    ),
                'amountInNumber' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 10,
                        'conf' => 100,
                    ),
                'base64AlignedJPEG' => 'djdnj',
                'endDate' =>
                    array (
                        'value' => '',
                        'conf' => 0,
                    ),
                'sponsorCode' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'RATN0TREASU',
                        'conf' => 100,
                    ),
                'signaturePresentPrimary' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'yes',
                        'conf' => 100,
                    ),
                'secondaryAccountHolder' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'RANJITH',
                        'conf' => 100,
                    ),
                'startDate' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => '07/12/2025',
                        'conf' => 100,
                    ),
                'primaryAccountHolder' =>
                    array (
                        'to-be-reviewed' => 'no',
                        'value' => 'TEST',
                        'conf' => 100,
                    ),
            ),
    ),

    'testCreatePaymentForNach' => [
        'request' => [
            'content' => [
                "amount"      => 0,
                "currency"    => "INR",
                "method"      => "nach",
                "order_id"    => "order_100000000order",
                "customer_id" => "cust_1000000000cust",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/ajax',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ]
    ],

    'testCreatePaymentForNachFormNotSubmitted' => [
        'request' => [
            'content' => [
                "amount"      => 0,
                "currency"    => "INR",
                "method"      => "nach",
                "order_id"    => "order_100000000order",
                "customer_id" => "cust_1000000000cust",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/ajax',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'payment can\'t be created without nach form submission',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePaymentNachForAlreadyActivePaymentForNach' => [
        'request' => [
            'content' => [
                "amount"      => 0,
                "currency"    => "INR",
                "method"      => "nach",
                "order_id"    => "order_100000000order",
                "customer_id" => "cust_1000000000cust",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/ajax',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'payment pay_1000000payment is not failed for the given order which is of method nach, can\'t create one more',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testRetryTokenForNach' => [
        'request' => [
            'content' => [
            ],
            'method'    => 'POST',
            'url'       => '/token.registration/paper_mandate/token/token_100000000token/retry',
        ],
        'response'  => [
            'content'     => [
            ],
            'status_code' => 200,
        ],
    ],
];
