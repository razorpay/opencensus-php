<?php

namespace RZP\Tests\Functional\PaperMandate;

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use Carbon\Carbon;

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
                        'beneficiary_name' => 'Gaurav Kumar lllllllllllllllllllllllllllllllllllll',
                        'beneficiary_email' => 'gaurav.kumarwqqqwqwjkhwdjbwhbdbhjdbdbjdwbhjdbjd11111111111111111@example.com',
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

    'testCreateAuthLinkForPaperMandateSBNRO' => [
        'request' => [
            'content' => [
                'amount'          => 0,
                'currency'        => 'INR',
                'method'          => 'nach',
                'payment_capture' => 1,
                'customer_id'     => 'cust_100000customer',
                'token'           =>
                    [
                        'auth_type'            => 'physical',
                        'first_payment_amount' => '5000',
                        'max_amount'           => '500000',
                        'expire_at'            => '2047483647',
                        'nach'                 => [
                            'create_form' => true,
                        ],
                        'bank_account'         =>
                            [
                                'account_type'       => 'nro',
                                'bank_name'          => 'HDFC',
                                'account_number'     => '1111111111111',
                                'ifsc_code'          => 'HDFC0001233',
                                'beneficiary_name'   => 'Gaurav Kumar',
                                'beneficiary_email'  => 'gaurav.kumar@example.com',
                                'beneficiary_mobile' => '9483159238'
                            ]
                    ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 0,
                'currency' => 'INR',
                'token'    => [
                    'auth_type' => 'physical',
                    'method'    => 'nach',
                    'nach'      => [
                        'create_form'     => true,
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
                        'first_payment_amount' => '5000',
                        'max_amount' => '500000',
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

    'testUploadValidatePaperMandate' => [
        'request' => [
            'content' => [
                'auth_link_id' => 'inv_1000000invoice',
            ],
            'method'    => 'POST',
            'url'       => '/token.registration/paper_mandate/validate/proxy',
        ],
        'response' => [
            'content' => [
                'status'  => 'accepted',
                'status_reason' => null,
                'email_id' => "gaurav.kumar12@example.com",
                'amount_in_words' => "TEN",
                'utility_code' => "NACH00000000013149",
                'reference_1' => "121211212112121121",
                'bank_name' => "HDFC BANK",
                'debit_type' => "maximum_amount",
                'micr' => "",
                'frequency' => "as_and_when_presented",
                'signature_present_tertiary' => "no",
                'until_cancelled' => "true",
                'signature_present_secondary' => "no",
                'nach_type' => "create",
                'account_number' => "1111111111111",
                'nach_date' => "19/08/2019",
                'phone_number' => "9123456780",
                'tertiary_account_holder' => "THE DON",
                'umrn' => "",
                'company_name' => "TEST",
                'ifsc_code' => "HDFC0000123",
                'reference_2' => "121211212112121121",
                'account_type' => "savings",
                'amount_in_number' => "1000",
                'end_date' => "",
                'sponsor_code' => "RATN0TREASU",
                'signature_present_primary' => "yes",
                'secondary_account_holder' => "",
                'start_date' => "07/12/2025",
                'primary_account_holder' => "TEST",
                'form_checksum' => "XXXXXXX",
                'not_matching' => [],
                'success' => true,
            ],
        ],
    ],

    'testReuseUploadValidateInSubmitPaperMandate' => [
        'request' => [
            'content' => [
                'auth_link_id' => 'inv_1000000invoice',
                'paper_mandate_upload_id' => 'pmu_100000000000000',
            ],
            'method'    => 'POST',
            'url'       => '/token.registration/paper_mandate/authenticate/proxy',
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
        'ifsc_code' => 'HDFC0000123',
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
                'razorpay_order_id' => 'order_100000000order',
            ],
            'status_code' => 200,
        ]
    ],

    'testCreatePaymentForNachAuto' => [
        'request' => [
            'content' => [
                "amount"      => 10000,
                "currency"    => "INR",
                "order_id"    => "order_100000001order",
                "customer_id" => "cust_1000000000cust",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
                "token"       => 'token_id',
            ],
            'method'    => 'POST',
            'url'       => '/payments/create/recurring',
        ],
        'response'  => [
            'content'     => [
                'razorpay_order_id' => 'order_100000001order',
            ],
            'status_code' => 200,
        ]
    ],

    'testCreatePaymentForNachAutoProxyAuth' => [
        'request' => [
            'content' => [
                "amount"      => 10000,
                "currency"    => "INR",
                "order_id"    => "order_100000001order",
                "customer_id" => "cust_1000000000cust",
                "recurring"   => true,
                "contact"     => "9483159238",
                "email"       => "r@g.c",
                "token"       => 'token_id',
            ],
            'method'    => 'POST',
            'url'       => '/subscription_registration/tokens/{id}/charge',
        ],
        'response'  => [
            'content'     => [],
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
                    'description' => 'A form against this order is pending action on the destination bank. A new form cannot be submitted till a status is received',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_NACH_FORM_STATUS_PENDING
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

    'testCreatePaperMandateSpecialCharsInName' => [
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
                                'beneficiary_name' => 'Gaurav.Kumar@ something  else',
                                'beneficiary_mobile' => '9483159238'
                            ]
                    ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'token'          =>   [
                    'bank_account' => [
                        'name'     => 'Gaurav Kumar something else',
                    ],
                ],
            ],
        ],
    ],

    'testCreateAuthLinkForPaperMandateWithMergedBank' => [
        'request'  => [
            'content' => [
                'amount'          => 0,
                'currency'        => 'INR',
                'method'          => 'nach',
                'receipt'         => 'rcptid #1',
                'payment_capture' => 1,
                'customer_id'     => 'cust_100000customer',
                'token'           =>
                    [
                        'auth_type'            => 'physical',
                        'first_payment_amount' => '5000',
                        'max_amount'           => '500000',
                        'expire_at'            => '2047483647',
                        'nach'                 => [
                            'create_form'     => true,
                            'form_reference1' => 'ttt',
                            'form_reference2' => 'qqq',
                        ],
                        'bank_account'         =>
                            [
                                'account_number'     => '1111111111111',
                                'ifsc_code'          => 'ALLA0212522',
                                'beneficiary_name'   => 'Gaurav Kumar lllllllllllllllllllllllllllllllllllll',
                                'beneficiary_email'  => 'gaurav.kumarwq11111111111111@example.com',
                                'beneficiary_mobile' => '9483159238'
                            ]
                    ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 0,
                'currency' => 'INR',
                'receipt'  => 'rcptid #1',
                'token'    => [
                    'auth_type'    => 'physical',
                    'method'       => 'nach',
                    'bank_account' => [
                        'ifsc'      => 'IDIB000C080',
                        'bank_name' => 'Indian Bank',
                    ],
                ],
            ],
        ],
    ],
];
