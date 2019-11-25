<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testCreateOrder' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
        ],
    ],

    'testCreateOrderForNonRegisteredBusinessLessThanMaxAmount' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
            ],
        ],
    ],

    'testCreateOrderForNonRegisteredBusinessMoreThanMaxAmount' => [
        'request'   => [
            'content' => [
                'amount'   => 2500001,
                'currency' => 'INR',
                'receipt'  => 'rcptid42',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount exceeds maximum amount allowed.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUniqueReceiptFeatureWithNoReceipt' => [
        'request'   => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_REQUIRED,
                    'field'       => 'receipt'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUniqueReceiptFeatureWithValidReceipt' => [
        'request'  => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
                'receipt'  => 'rcptid42',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
                'receipt'  => 'rcptid42',
            ],
        ],
    ],

    'testInvalidCurrency' => [
        'request'   => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'XYZ',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Currency is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_CURRENCY_NOT_SUPPORTED
        ],
    ],

    'testValidCurrencyForConvertSupport' => [
        'request'   => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'USD',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'USD',
            ],
        ],
    ],

    'testUniqueReceiptFeatureWithDuplicateReceipt' => [
        'request'   => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
                'receipt'  => 'rcptid42',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_RECEIPT_NOT_UNIQUE,
                    'field'       => []
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateOrderWithTwoNullReceipts' => [
        'request'  => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
            ],
        ],
    ],

    'testCreateOrderWithTwoValidReceipts' => [
        'request'  => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
                'receipt'  => 'rcptid42',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
                'receipt'  => 'rcptid42',
            ],
        ],
    ],

    'testCreateOrderWithNegativeAmount' => [
        'request' => [
            'content' => [
                'amount'          => -200,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'payment_capture' => '1'
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
         'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_AMOUNT_LESS_THAN_MIN_AMOUNT,
                    'field' => 'amount'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateOrderWithoutReceipt' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/orders',
            'content' => [
                'amount'   => 50000,
                'currency' => 'INR',
            ],
        ],
        'response' => [
            'content' => [
                'entity'      => 'order',
                'amount'      => 50000,
                'amount_paid' => 0,
                'amount_due'  => 50000,
                'currency'    => 'INR',
                'receipt'     => null,
                'offer_id'    => null,
                'status'      => 'created',
                'attempts'    => 0,
                'notes'       => [],
            ],
        ],
    ],

    'testCreateAutoCaptureOrder' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'payment_capture' => '1',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'status'        => 'created'
            ],
        ],
    ],

    'testCreateTPVOrder' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'account_number' => '040304030403040',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testCreateTPVOrderWithoutAccountNumber' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Account number is mandatory for this merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_ACCOUNT_NUMBER_REQUIRED_FOR_MERCHANT
        ],
    ],

    'testCreateTPVOrderWithNewFlow' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank_account'   => [
                    'account_number'    => '040304030403040',
                    'ifsc'              => 'UTIB0003098',
                    'name'              => 'ThisIsAwesome',
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testCreateTPVOrderEmptyMethod' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'account_number' => '040304030403040',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testCreateTPVOrderUpiBank' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'account_number' => '040304030403040',
                'bank'           => 'JSBP',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testCreateTPVOrderUpiBankInconsitentIfsc' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'account_number' => '040304030403040',
                'bank'           => 'PUNB_R',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testCreateTPVOrderInvalidMethod' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'card',
                'account_number' => '040304030403040',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
         'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected method is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testCreateOrderWithBank' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],
    'testEMandateOrderWithCustomerFeeBearer' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Order creation failed. Please contact Razorpay for further assistance.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testEmandateRegistrationOrderWithZeroRupee' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],
    'testEmandateRegistrationOrderWithTokenMaxAmount' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
                'customer_id'    => 'cust_100000customer',
                'payment_capture'=> 1,
                'token'          => [
                    'method'       => 'emandate',
                    'max_amount'   => 2500,
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc_code'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'token'          =>   [
                    'method'       => 'emandate',
                    'max_amount'   => 2500,
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
        ],
    ],
    'testEmandateRegistrationOrderWithZeroRupeeAndTokenWithoutCustomer' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
                'token'          => [
                    'method'       => 'emandate',
                    'expire_at'    => '1880118306',
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc_code'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Customer Id is required with token field',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testEmandateRegistrationOrderWithZeroRupeeAndToken' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
                'customer_id'    => 'cust_100000customer',
                'token'          => [
                    'method'       => 'emandate',
                    'expire_at'    => '1880118306',
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc_code'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'token'          =>   [
                    'method'       => 'emandate',
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
        ],
    ],
    'testEmandateRegistrationOrderWithZeroRupeeAndTokenWithFirstAmount' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
                'customer_id'    => 'cust_100000customer',
                'token'          => [
                    'method'                => 'emandate',
                    'expire_at'             => 1880118306,
                    'first_payment_amount'  => 100,
                    'auth_type'             => 'netbanking',
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc_code'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'token'          =>   [
                    'method'       => 'emandate',
                    'expire_at'             => 1880118306,
                    'first_payment_amount'  => 100,
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
        ],
    ],
    'testTokenRegistrationOrderWithDifferentMethod' =>
    [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
                'customer_id'    => 'cust_100000customer',
                'token'          => [
                    'method'                => 'card',
                    'expire_at'             => 1880118306,
                    'first_payment_amount'  => 100,
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc_code'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'order method doesn\'t match with token method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testTokenRegistrationOrderWithoutMethod' =>
    [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'UTIB',
                'customer_id'    => 'cust_100000customer',
                'token'          => [
                    'expire_at'             => 1880118306,
                    'first_payment_amount'  => 100,
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc_code'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'beneficiary_name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'token'          =>   [
                    'expire_at'             => 1880118306,
                    'first_payment_amount'  => 100,
                    'bank_account' => [
                        'bank_name'          => 'HDFC Bank',
                        'ifsc'          => 'HDFC0001233',
                        'account_number'     => '123312563456',
                        'account_type'       => 'savings',
                        'name'   => 'test',
                        'beneficiary_email'  => 'test@razorpay.com',
                        'beneficiary_mobile' => '9999999999'
                    ],
                ]
            ],
        ],
    ],
    'testEmandateRegistrationOrderWithoutZeroRupee' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'HDFC',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The amount must be at least 100.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testEmandateRegistrationOrderWithInvalidBank' => [
        'request' => [
            'content' => [
                'amount'         => 1000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'emandate',
                'bank'           => 'IDBI',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_BANK_INVALID
        ],
    ],
    'testGetOrder' => [
        'amount'        => 50000,
        'currency'      => 'INR',
        'receipt'       => 'rcptid42',
    ],

    'testGetMultipleOrders' => [
        'request' => [
            'url' => '/orders',
            'method' => 'get',
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testGetMultiplePaymentsForOrder' => [
        'request' => [
            'url' => '/orders/:id/payments',
            'method' => 'get',
            'content' => [
                'skip' => 1
            ]
        ],
        'response' => [
            'content' => []
        ]
    ],

    'testFetchOrder' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'entity'   => 'order',
            ],
        ],
    ],

    'testStatusAfterPayment' => [
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ALREADY_PAID
        ],
    ],

    'testPaymentForTPVMerchantWithoutOrder' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_ID_REQUIRED
        ],
    ],

    'testPaymentWithIncorrectBankForTPVMerchantWithOrder' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Order bank does not match the payment bank',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testOrderAndPaymentAmountMismatch' => [
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
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_ORDER_AMOUNT_MISMATCH
        ],
    ],

    'testPreferencesForTPVMerchants' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'netbanking' => [
                        'UTIB' => 'Axis Bank',
                    ],
                ],
                'order' => [
                    'bank'           => 'UTIB',
                    'account_number' => 'XXXXXXXXXXXXX40',
                    'method'         => 'netbanking',
                ],
            ],
        ],
    ],

    'testPreferencesForTPVMerchantsEmptyMethod' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'netbanking' => [
                        'UTIB' => 'Axis Bank',
                    ],
                    'upi' => true,
                ],
                'order' => [
                    'bank'           => 'UTIB',
                    'account_number' => 'XXXXXXXXXXXXX40',
                ],
            ],
        ],
    ],

    'testPreferencesForTPVMerchantsEmptyMethodInvalidBank' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'upi' => true,
                ],
                'order' => [
                    'bank'           => 'JSBP',
                    'account_number' => 'XXXXXXXXXXXXX40',
                ],
            ],
        ],
    ],

    'testPreferencesForOrderWithBank' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'methods' => [
                    'netbanking' => [
                        'UTIB' => 'Axis Bank',
                    ],
                ],
            ],
        ],
    ],

    'testPreferencesForOrderWithAuthType' => [
        'request' => [
            'content' => [],
            'url' => '/preferences',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'order' => [
                    'auth_type' => 'netbanking',
                ],
            ],
        ],
    ],

    'testCreateOrderWithOffer' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null,
            ],
        ],
    ],

    'testCreateOrderWithOfferUpdatedFormat' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offers'        => [
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null,
                'offers'        => null,
            ],
        ],
    ],


    'testCreateOrderWithRepeatedOffers' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offers'        => [
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null,
                'offers'        => null,
            ],
        ],
    ],

    'testCreateOrderWithOffersAndOfferID' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null,
                'offers'        => [
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Request should send either offer_id or offers',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateOrderWithMultipleOffers' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offers'        => [
                ],
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offers'        => null,
            ],
        ],
    ],

    'testCreateOrderWithOfferAndDiscounting' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'offer_id'      => null,
                // 'discount'      => true,
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'offer_id'      => null,
                // 'discount'      => true,
            ],
        ],
    ],

    'testPaymentWithIncorrectBankFromOrderBank' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_BANK_DOES_NOT_MATCH_PAYMENT_BANK
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_BANK_DOES_NOT_MATCH_PAYMENT_BANK
        ]
    ],
    'testCreateOrderWithNotApplicableOffer' => [
        'request' => [
            'content' => [
                'amount'        => 900,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_INVALID_OFFER
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER
        ]
    ],

    'testCreateOrderWithExpiredOffer' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'offer_id'      => null
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_INVALID_OFFER
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER
        ]
    ],

    'testPaymentWithFailedOfferCheck' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Offer Payment Method is not same as Selected Payment Method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithFailedOfferCheckOnInternational' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Selected Card is not international but offer applied requires international card',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithFailedOfferCheckOnNullMethodOffer' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Custom error message',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithFailedOfferWithCustomErrorMessage' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Offer Payment Method is not same as Selected Payment Method',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithOfferOnNullMethodAndIinAndIssuer' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Selected card does not belong to offer iins',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPaymentWithMaxPaymentCountOfferAppliedOnOrderWithNoCardSaving' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::MAX_CARD_USAGE_LIMIT_EXCEEDED
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPaymentWithMaxPaymentCountAppliedOnOrderWithGlobalSavedCard' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::MAX_CARD_USAGE_LIMIT_EXCEEDED
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPaymentWithMaxPaymentCountAppliedOnOrderWithLocallySavedCard' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::MAX_CARD_USAGE_LIMIT_EXCEEDED
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPaymentWithMaxPaymentCountOfferButPaymentsAlreadyMadeOnLinkedOffers' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method used is not eligible for offer. Please try with a different payment method.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testPartialPaymentExcessAmountManualCaptureFailure' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'BAD_REQUEST_PAYMENT_AMOUNT_MORE_THAN_ORDER_AMOUNT_DUE'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testOrderEditNotes' => [
        'request'  => [
            'content' => [
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ],
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                ],
            ],
            'status_code' => 200,
        ],
    ],
];
