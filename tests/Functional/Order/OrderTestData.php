<?php

use Carbon\Carbon;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [

    'testCreateOrderLiveModeNonKycActivatedNonVaActivatedExperimentOn' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42'
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => PublicErrorCode::BAD_REQUEST_ERROR,
        ]
    ],


        'testCreateOrderLiveModeNonKycActivatedNonCaActivatedExperimentOff' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42'
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_NOT_ACTIVATED_FOR_LIVE_REQUEST,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => PublicErrorCode::BAD_REQUEST_ERROR,
        ]
    ],

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

    'testCreateOrderMYRMerchantMY' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'MYR',
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
                'currency'      => 'MYR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
        ],
    ],

    'testCreateOrderINRMerchantMY' => [
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

    'testCreateOrderAdminAuthRoute' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
            'method'    => 'POST',
            'url'       => '/admin/orders',
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

    'testCreateOrderAdminAuthRouteMerchantNotHavingFeature' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                // 'method'     => 'netbanking',
                // 'account_id' => '0040304030403040',
            ],
            'method'    => 'POST',
            'url'       => '/admin/orders',
        ],
        'response' => [
            'content'  => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400
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

    'testCreateOrderWithEmptyArrayTransfersParam' => [
        'request' => [
            'url'       => '/orders',
            'method'    => 'POST',
            'content'   => [
                'amount'            => 50000,
                'currency'          => 'INR',
                'payment_capture'   => true,
                'transfers'         => [],
            ],
        ],
        'response' => [
            'content' => [
                'entity'        => 'order',
                'amount'        => 50000,
                'currency'      => 'INR',
                'status'        => 'created',
            ],
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

    'testCurrencyForTurkishLiraEnabled' => [
        'request'   => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'TRY',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'amount'   => 50000,
                'currency' => 'TRY',
            ],
        ],
    ],

    'testCurrencyForShaadiComWithFeatureEnabled' => [
        'request'   => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'BHD',
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'amount'   => 50000,
                'currency' => 'BHD',
            ],
        ],
    ],

    'testCurrencyForShaadiComWithFeatureNotEnabled' => [
        'request'   => [
            'content' => [
                'amount'   => 50000,
                'currency' => 'BHD',
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

    'testUniqueReceiptErrorFeatureWithDuplicateReceipt' => [
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

    'testCreateOrderWithPhonepeSwitchContext' => [
        'request' => [
            'content' => [
                'amount'                 => 50000,
                'currency'               => 'INR',
                'payment_capture'        => '1',
                'receipt'                => 'merchant_txn_id',
                'phonepe_switch_context' => "{\"transactionContext\":{\"orderContext\":{\"trackingInfo\":{\"type\":\"HTTPS\",\"url\":\"https://google.com\"}},\"fareDetails\":{\"payableAmount\":3900,\"totalAmount\":3900},\"cartDetails\":{\"cartItems\":[{\"quantity\":1,\"address\":{\"addressString\":\"TEST\",\"city\":\"TEST\",\"pincode\":\"TEST\",\"country\":\"TEST\",\"latitude\":1,\"longitude\":1},\"shippingInfo\":{\"deliveryType\":\"STANDARD\",\"time\":{\"timestamp\":1561540218,\"zoneOffSet\":\"+05:30\"}},\"category\":\"SHOPPING\",\"itemId\":\"1234567890\",\"price\":3900,\"itemName\":\"TEST\"}]}}}",
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'merchant_txn_id',
            ],
        ],
    ],

    'testCreateOrderWithInvalidPhonepeSwitchContext' => [
        'request' => [
            'content' => [
                'amount'                 => 50000,
                'currency'               => 'INR',
                'payment_capture'        => '1',
                'receipt'                => 'merchant_txn_id',
                'phonepe_switch_context' => "transactionContext",
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The phonepe switch context must be a valid JSON string.',
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


    'testCreateOrderAccountInvalidBankIfscWithFeatureDisabled' => [
        'request'   => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank_account'      => [
                    'ifsc'           => 'UTIB0003089',
                    'name'           => 'ThisIsAwesome',
                    'account_number' => '040304030403040'
                ],
            ],
            'url'     => '/orders',
            'method'  => 'POST'
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
            ],
        ],
    ],

    'testCreateOrderAccountInvalidBankIfscWithFeatureEnable' => [
        'request'   => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'netbanking',
                'bank_account'      => [
                    'ifsc'           => 'UTIB00030',
                    'name'           => 'ThisIsAwesome',
                    'account_number' => '040304030403040'
                ],
            ],
            'url'     => '/orders',
            'method'  => 'POST'
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid IFSC Code in Bank Account',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testCreateUpiTPVOrderOldRequestFormat' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'upi',
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

    'testCreateUpiRecurringTPVOrder' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'upi',
                'bank_account'   => [
                    'account_number'    => '040304030403040',
                    'ifsc'              => 'ICIC0001183',
                    'name'              => 'UpiRecurringTPVOrder',
                ],
                'customer_id' => 'cust_100000customer',
                'payment_capture' => 1,
                'token' => [
                    'max_amount'        => 150000,
                    'frequency'         => 'monthly',
                    'recurring_type'    => 'before',
                    'recurring_value'   => 30,
                    'start_at'          => Carbon::now()->addDay(1)->getTimestamp(),
                    'expire_at'         => Carbon::now()->addDay(60)->getTimestamp(),
                ]
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

    'testCreateUpiTPVOrderNewRequestFormat' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'upi',
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

    'testCreateUpiTPVOrderNewRequestOldIfsc' => [
        'request' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'upi',
                'bank_account'   => [
                    'account_number'    => '040304030403040',
                    'ifsc'              => 'CORP0003538',
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

    'testCreateCardTPVOrder' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'method'          => 'card',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'   => 300000,
                    'expire_at'    => 1880118306,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'card',
                'token'          =>   [
                    'max_amount'   => 300000,
                    'expire_at'    => 1880118306,
                ]
            ],
        ],
    ],

    'testCreateCardWithNoMaxAmount' => [
        'request' => [
            'content' => [
                'amount'          => 150000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'method'          => 'card',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'expire_at'    => 1880118306,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 150000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'card',
                'token'          =>   [
                    'max_amount'   => 1500000,
                    'expire_at'    => 1880118306,
                ]
            ],
        ],
    ],

    'testCreateCardWithMaxAmountMoreThan1000000' => [
        'request' => [
            'content' => [
                'amount'          => 150000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'method'          => 'card',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'   => 100000100,
                    'expire_at'    => 1880118306,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The max amount may not be greater than 100000000.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCardWithMaxAmountLessThanZero' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'method'          => 'card',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'   => -10000,
                    'expire_at'    => 1880118306,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The max amount should be greater than zero.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateTPVOrderWhenMethodNull' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'   => 300000,
                    'expire_at'    => 1880118306,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         =>  null,
                'token'          =>   [
                    'max_amount'   => 300000,
                    'expire_at'    => 1880118306,
                ]
            ],
        ],
    ],

    'testCreateCardTPVOrderNoMaxAmount' => [
        'request' => [
            'content' => [
                'amount'          => 150000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'method'          => 'card',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'expire_at'    => 1880118306,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 150000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'card',
                'token'          =>   [
                    'max_amount'   => 1500000,
                    'expire_at'    => 1880118306,
                ]
            ],
        ],
    ],

    'testCreateCardTPVOrderNoExpireAt' => [
        'request' => [
            'content' => [
                'amount'          => 50000,
                'currency'        => 'INR',
                'receipt'         => 'rcptid42',
                'method'          => 'card',
                'customer_id'     => 'cust_100000customer',
                'payment_capture' => 1,
                'token'           => [
                    'max_amount'   => 300000,
                ]
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'         => 50000,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'card',
                'token'          =>   [
                    'max_amount'   => 300000,
                    'expire_at'    => null,
                ]
            ],
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
                    'description' => 'emandate is not supported for customer fee bearer model. Please contact support for more details.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],
    'testNachOrderWithCustomerFeeBearer' => [
        'request' => [
            'content' => [
                'amount'         => 0,
                'currency'       => 'INR',
                'receipt'        => 'rcptid42',
                'method'         => 'nach',
                'bank'           => 'UTIB',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'nach is not supported for customer fee bearer model. Please contact support for more details.',
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

    'testFetchOrderDetailForExpressAuth' => [
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

    'testFetchOrderDetailNotExpressAuthError' => [
        'request' => [
            'method'  => 'GET',
            'content' => [],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
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
                    'description' => "Your payment could not be completed due to a temporary technical issue. To complete the payment, use another payment method.",
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
                    'upi_intent' => true,
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
                    'upi_intent' => true,
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

    'testCreateOrderWithOfferIDDifferentProduct' => [
        'request' => [
            'content' => [
                'amount'        => 1100,
                'currency'      => 'INR',
                'offer_id'      => null,
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_ORDER_INVALID_OFFER,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_INVALID_OFFER,
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
                    'description' => 'Payment Method is not available for this Offer',
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
                    'description' => 'Offer not applicable on selected issuer',
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
                    'description' => 'Payment Method is not available for this Offer',
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
                    'description' => PublicErrorDescription::OFFER_MAX_CARD_USAGE_LIMIT_EXCEEDED
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
                    'description' => PublicErrorDescription::OFFER_MAX_CARD_USAGE_LIMIT_EXCEEDED
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
                    'description' => PublicErrorDescription::OFFER_MAX_CARD_USAGE_LIMIT_EXCEEDED
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

    'testCreateOrderWithConfigId' => [
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

    'testCreateOrderWithValidProductType' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'product_type'  => 'invoice',
                'product_id'    => 'somerandtestId',
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

    'testCreateOrderWithPlV2ProductType' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'product_type'  => 'payment_link_v2',
                'product_id'    => 'somerandtestId',
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

    'testCreateOrderWithInvalidValidProductType' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'product_type'  => 'thisisinvalid',
                'product_id'    => 'somerandtestId',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid product type: thisisinvalid'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOrderWithProductIdMissing' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'product_type'  => 'invoice',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The product id field is required when product type is present.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testCreateOrderWithProductTypeMissing' => [
        'request' => [
            'content' => [
                'amount'        => 50000,
                'currency'      => 'INR',
                'receipt'       => 'rcptid42',
                'product_id'    => 'somerandtestId',
            ],
            'method'    => 'POST',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The product type field is required when product id is present.'
                ]
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ]
    ],

    'testUpdateOrderWithPartialPayment' => [
        'request' => [
            'content' => [
                'partial_payment'           => true,
                'first_payment_min_amount'  => 3434,
            ],
            'method'    => 'PATCH',
            'url'       => '/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 50000,
            ],
        ],
    ],

    'testUpdateOrderWithPartialPaymentWithInvalidProductType' => [
        'request' => [
            'content' => [
                'partial_payment'           => true,
                'first_payment_min_amount'  => 3434,
            ],
            'method'    => 'PATCH',
            'url'       => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'partial payment update not allowed',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testUpdateOrderWithPartialPaymentWithLargerAmount' => [
        'request' => [
            'content' => [
                'partial_payment'           => true,
                'first_payment_min_amount'  => 34343434,
            ],
            'method'    => 'PATCH',
            'url'       => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'minimum amount should be less than 50000',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testUpdateOrderSuccessFromPGRouter' => [
        'request' => [
            'content' => [
                'status'           => "paid",
            ],
            'method'    => 'PATCH',
            'url'       => '/internal/orders',
        ],
        'response' => [
            'content' => [
                'amount'        => 1000000,
                'currency'      => 'INR',
                'status'        => "paid"
            ],
        ],
    ],

    'testUpdateOrderSuccessThroughOrderOutbox' => [
        'request' => [
            'url'       => '/order_outbox/retry',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                "successful entries count" => 1
            ],
        ],
    ],

    'testUpdateOrderFailureThroughOrderOutbox' => [
        'request' => [
            'url'       => '/order_outbox/retry',
            'method'    => 'POST',
        ],
        'response' => [
            'content' => [
                "failed entries count" => 1
            ],
        ],
    ],

    'testCreateOrderWithAmountGreaterThanMaxAmountAndCurrencyUSD' => [
        'request'   => [
            'content' => [
                'amount'   => 1001,
                'currency' => 'USD',
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

    'testOrderForMerchantDisabledBank' => [
        'request'   => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'bank'     => 'ICIC'
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested bank is not enabled for the merchant',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_ORDER_BANK_NOT_ENABLED_FOR_MERCHANT
        ],
    ],
    'testCreateOrderWithConvenienceFeeConfigEmpty' => [
        'request' => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'convenience_fee_config' => []
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'amount' => 1000,
                'currency' => 'INR'
            ],

        ],
    ],

    'testCreateOrderWithConvenienceFeeConfigMerchantNotOnDynamicFeeBearer' => [
        'request' => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'convenience_fee_config' => [
                    'rules' => []
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Convenience fee configurable for dynamic fee bearer users only',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_INVALID_CONVENIENCE_FEE_CONFIG
        ],
    ],

    'testCreateOrderWithConvenienceFeeConfigMerchantOnDynamicFeeBearer' => [
        'request' => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR',
                'method'   => 'netbanking',
                'convenience_fee_config' => [
                    "rules" => [
                        [
                            "method" => "netbanking",
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 20
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'amount' => 1000,
                'currency' => 'INR',
                'convenience_fee_config' => [
                    "rules" => [
                        [
                            "method" => "netbanking",
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 20
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testCreateOrderWithConvenienceFeeConfigWithDifferentCurrency' => [
        'request' => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'USD',
                'method'   => 'card',
                'convenience_fee_config' => [
                    "rules" => [
                        [
                            "method" => "netbanking",
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 20
                            ]
                        ]
                    ]
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'amount' => 1000,
                'currency' => 'USD',
                'convenience_fee_config' => [
                    "rules" => [
                        [
                            "method" => "netbanking",
                            "fee" => [
                                "payee" => "customer",
                                "flat_value" => 20
                            ]
                        ]
                    ]
                ]
            ],
        ],
    ],

    'testFetch1ccOrderWithOffer' => [
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

    'testCreateOrderFor1CCWithInvalidLineItems' => [
        'request' => [
            'content' => [
                'amount'   => 1000,
                'currency' => 'INR',
                'line_items_total' => 1000,
                'line_items' => [
                    [
                        'name' => 'name',
                        'price' => 'price',
                        'quantity' => 1,
                    ],
                ]
            ],
            'method'  => 'POST',
            'url'     => '/orders',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testOrderNoteUpdateFor1CC' => [
        'request' => [
            'method' => 'patch',
            'content' => [
                "gstin" => "12345abcde98765",
                "order_instructions" => "deliver it early"
            ]
        ],
        'response' => [
            'content'     => [],
            'status_code' => 200
        ],
    ],

    'testFetchOrderDetailsForCheckout' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/internal/orders/checkout',
        ],
        'response'  => [
            'content' => [
                'partial_payment'   => false,
                'amount'            => 50000,
                'currency'          => 'INR',
                'amount_paid'       => 0,
                'amount_due'        => 50000,
                'first_payment_min_amount' => null,
            ],
        ],
    ],

    'testFetchOrderDetailsForCheckoutWithAllPossibleFieldsInResponse' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/internal/orders/checkout',
        ],
        'response'  => [
            'content' => [
                'partial_payment'   => false,
                'amount'            => 50000,
                'currency'          => 'INR',
                'amount_paid'       => 0,
                'amount_due'        => 50000,
                'first_payment_min_amount' => null,
                'bank' => 'UTIB',
                'account_number' => 'XXXXXXXXXXXXX40',
                'method' => 'netbanking',
                'line_items_total' => 50000,
                'line_items' => [
                    [
                        'type' => 'e-commerce',
                        'sku' => '1g234',
                        'variant_id' => '12r34',
                        'other_product_codes' => [
                            'upc' => '12r34',
                            'ean' => '123r4',
                            'unspsc' => '123s4'
                        ],
                        'price' => '20000',
                        'offer_price' => '20000',
                        'tax_amount' => 0,
                        'quantity' => 1,
                        'name' => 'TEST',
                        'description' => 'TEST',
                        'weight' => '1700',
                        'dimensions' => [
                            'length' => '1700',
                            'width' => '1700',
                            'height' => '1700'
                        ],
                        'image_url' => 'http://url',
                        'product_url' => 'http://url',
                        'notes' => []
                    ],
                    [
                        'type' => 'e-commerce',
                        'sku' => '1g235',
                        'variant_id' => '12r34',
                        'other_product_codes' => [
                            'upc' => '12r34',
                            'ean' => '123r4',
                            'unspsc' => '123s4'
                        ],
                        'price' => '30000',
                        'offer_price' => '30000',
                        'tax_amount' => 0,
                        'quantity' => 1,
                        'name' => 'TEST',
                        'description' => 'TEST',
                        'weight' => 1700,
                        'dimensions' => [
                            'length' => 1700,
                            'width' => 1700,
                            'height' => 1700
                        ],
                        'image_url' => 'http://url',
                        'product_url' => 'http://url',
                        'notes' => []
                    ]
                ]
            ],
        ],
    ],

    'testFetchOrderDetailsForCheckoutWithExpandOrder' => [
        'request'  => [
            'method'  => 'POST',
            'url'     => '/internal/orders/checkout',
            'content' => [
                'expand' => [
                    'order',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'account_number'           => 'XXXXXXXXXXXXX40',
                'amount'                   => 50000,
                'amount_due'               => 50000,
                'amount_paid'              => 0,
                'bank'                     => 'UTIB',
                'currency'                 => 'INR',
                'first_payment_min_amount' => null,
                'line_items_total'         => 50000,
                'line_items'               => [
                    [
                        'name'     => 'Line Item 1',
                        'price'    => 10000,
                        'quantity' => 1,
                    ],
                    [
                        'name'     => 'Line Item 2',
                        'price'    => 20000,
                        'quantity' => 2,
                    ],
                ],
                'method'                   => 'netbanking',
                'order'                    => [
                    'id'                       => '', // Filled by the test
                    'account_number'           => 'XXXXXXXXXXXXX40',
                    'amount'                   => 50000,
                    'amount_due'               => 50000,
                    'amount_paid'              => 0,
                    'app_offer'                => false,
                    'attempts'                 => 0,
                    'authorized'               => false,
                    'bank'                     => 'UTIB',
                    'checkout_config_id'       => null,
                    'currency'                 => 'INR',
                    'customer_id'              => null,
                    'discount'                 => false,
                    'first_payment_min_amount' => null,
                    'force_offer'              => null,
                    'late_auth_config_id'      => null,
                    'line_items_total'         => 50000,
                    'merchant_id'              => '10000000000000',
                    'method'                   => 'netbanking',
                    'notes'                    => [],
                    'offers'                   => [
                        'count'  => 0,
                        'entity' => 'collection',
                        'items'  => [],
                    ],
                    'offer_id'                 => null,
                    'order_metas'              => [
                        [
                            'order_id' => '', // Filled by the test
                            'type'     => 'one_click_checkout',
                            'value'    => [
                                'line_items'       => [
                                    [
                                        'name'     => 'Line Item 1',
                                        'price'    => 10000,
                                        'quantity' => 1,
                                    ],
                                    [
                                        'name'     => 'Line Item 2',
                                        'price'    => 20000,
                                        'quantity' => 2,
                                    ],
                                ],
                                'line_items_total' => 50000,
                            ],
                        ],
                    ],
                    'partial_payment'          => false,
                    'payer_name'               => 'ThisIsAwesome',
                    'payment_capture'          => null,
                    'pg_router_synced'         => 0,
                    'product_id'               => null,
                    'product_type'             => null,
                    'products'                 => [],
                    'provider_context'         => null,
                    'public_key'               => 'rzp_test_TheTestAuthKey',
                    'receipt'                  => 'R1',
                    'reference2'               => null,
                    'reference3'               => null,
                    'reference4'               => null,
                    'reference5'               => null,
                    'reference6'               => null,
                    'reference7'               => null,
                    'reference8'               => null,
                    'status'                   => 'created',
                ],
                'partial_payment'          => false,
            ],
        ],
    ],

    'testFetchOrderDetailsForCheckoutWithSubscriptionId' => [
        'request' => [
            'method'  => 'POST',
            'url'     => '/internal/orders/checkout',
            'content' => [
                'subscription_id' => '', // Filled by the TestCase
            ],
        ],
        'response'  => [
            'content' => [
                'partial_payment'   => false,
                'amount'            => 50000,
                'currency'          => 'INR',
                'amount_paid'       => 0,
                'amount_due'        => 50000,
                'first_payment_min_amount' => null,
            ],
        ],
    ],

    'test1CCOrderWithOffer' => [
        'request' => [
            'method' => 'GET',
            'content' => [
                'amount' => 100000,
            ]
        ],
        'response' => [
            'content' => [
                'offers' => [
                    [
                        'name' => "Test Offer",
                        'payment_method' => "card",
                        'payment_network' => "VISA",
                        'issuer' => "HDFC",
                        'type' => "instant",
                        'original_amount' => 100000,
                        'amount' => 90000,
                    ]
                ]
            ],
        ],
    ],
];
