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
                    'auth_type' => 'physical',
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
                        'bank_name' => 'HDFC',
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
                    'auth_type' => 'physical',
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

    'testCreateAuthLinkForPaperMandateWithoutAuthType' => [
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
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The auth type field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
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
                        'auth_type' => 'physical',
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
                    'auth_type' => 'physical',
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
                        'account_number',
                    ],
                ]
            ],
        ]
    ],

    'hyperVergeExtractNACHOutput' => [
        'email_id' => 'gaurav.kumar12@example.com',
        'amount_in_words' => 'TEN',
        'utility_code' => 'NACH00000000013149',
        'reference_1' => '121211212112121121',
        'bank_name' => 'HDFC BANK',
        'debit_type' => 'maximum_amount',
        'micr' => '',
        'frequency' => 'as_and_when_presented',
        'until_cancelled' => 'true',
        'nach_type' => 'create',
        'account_number' => '1111111111111',
        'nach_date' => '19/08/2019',
        'phone_number' => '9123456780',
        'umrn' => '',
        'company_name' => 'TEST',
        'ifsc_code' => 'RZPB0000000',
        'reference_2' => '121211212112121121',
        'account_type' => 'savings',
        'amount_in_number' => '1000',
        'enhanced_image' => 'djdnj',
        'end_date' => '',
        'sponsor_code' => 'RATN0TREASU',
        'primary_account_holder' => 'TEST',
        'signature_present_primary' => 'yes',
        'secondary_account_holder' => '',
        'signature_present_secondary' => 'no',
        'tertiary_account_holder' => 'THE DON',
        'signature_present_tertiary' => 'no',
        'start_date' => '07/12/2025',

        'form_checksum' => 'XXXXXXX',
    ],

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
                "auth_type"   => "physical",
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
                "auth_type"   => "physical"
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
                "auth_type"   => "physical"
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
