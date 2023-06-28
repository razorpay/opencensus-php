<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;
use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testCreatePaymentWithoutOrderId' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment processing failed due to missing order id',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_FAILED_MISSING_ORDER_ID
        ],
    ],

    'testSuccessCreatePaymentForMultipleCurrencies' => [
        [
            'currency' => 'AED',
            'amount' => '10'
        ],
        [
            'currency' => 'DZD',
            'amount' => '240'
        ],
        [
            'currency' => 'MVR',
            'amount' => '40'
        ],
        [
            'currency' => 'USD',
            'amount' => '50'
        ],
        [
            'currency' => 'SOS',
            'amount' => '1000'
        ],
    ],

    'testFailedCreatePaymentForMultipleCurrencies' => [
        'requestData' => [
            [
                'currency' => 'AED',
                'amount' => '9'
            ],
            [
                'currency' => 'DZD',
                'amount' => '210'
            ],
            [
                'currency' => 'GYD',
                'amount' => '400'
            ],
            [
                'currency' => 'USD',
                'amount' => '1'
            ],
            [
                'currency' => 'SOS',
                'amount' => '980'
            ],
        ],
        'responseData' => [
            'response'  => [
                'content'     => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
            ],
        ]
    ],

    'testCreatePaymentWithInvalidMethod' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid payment method given: invalid',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateCardPaymentFailedWithRestrictionUpi' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Payment method is not among the list of valid methods for order',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_METHOD_NOT_ALLOWED_FOR_ORDER
        ],
    ],

    'testCreatePaymentWithDisabledMethod' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment could not be completed as this bank is not enabled by the business. To complete the payment, use another account.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_NOT_ENABLED_FOR_MERCHANT
        ],
    ],

    'testCreatePaymentWithDisabledInstrument' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => "Your payment could not be completed due to a temporary technical issue. To complete the payment, use another payment instrument.",
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_INSTRUMENT_NOT_ENABLED
        ],
    ],

    'testCreatePaytmTestPaymentWithDisabledMethodWithDisabledTerminal' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_WALLET_NOT_ENABLED_FOR_MERCHANT,
        ],
    ],

    'testCreatePaymentWithoutMethod' => [
       'response' => [
           'content' => [
               'merchant_id'        => '10000000000000',
               'amount'             => 100,
               'currency'           => 'INR',
               'phase'              => 'chargeback',
               'status'             => 'open',
               'reason_description' => 'This is a serious fraud',
           ],
       ],
    ],

    'testCreatePaymentForNonRegisteredBusinessMoreThanMaxAmount' => [
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
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePaymentWithoutCardNumber' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The number field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreatePaymentWithoutContact' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The contact field is required.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testIntlPaymentWhenNotAllowed' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED,
                    'reason' => 'international_transaction_not_allowed',
                    'source' => 'business',
                    'step'   => 'payment_initiation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INTERNATIONAL_NOT_ALLOWED
        ],
    ],

    'testCreatePaymentInEs' => [
        'body' => [
            [
                'index' => [
                    '_index' => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
                    '_type'  => env('ES_ENTITY_TYPE_PREFIX').'payment_test',
                ],
            ],
            [
                'merchant_id' => '10000000000000',
            ],
        ],
    ],

    'testPaymentRoutedThroughCps' => [
        'request' => [
            'method'  => 'PUT',
            'url'     => '/config/keys',
            'content' => [
                'cps_service_enabled'        => '1',
            ],
        ]
    ],

    'testPaymentCreateCallingCallbackRouteTwiceForError' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CARD_INSUFFICIENT_BALANCE,
        ],
    ],

    'testSecondRecurringWithMissingBankAccountDetailsAndAuthType' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['axis'],
                'begin'   => Carbon::yesterday(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::today(Timezone::IST)->getTimestamp() - 1,
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'admin' => true,
                'items' => [
                    [
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'axis',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testEmandatePaymentCreateFailIfBankMissing' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The bank field is required when method is emandate.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPreferredRecurringPaymentInputValidation' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Request should contain either recurring or preferred_recurring, not both',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPreferredRecurringPaymentInputValidationInvalidMethod' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Recurring field may be sent only when method is card, eMandate',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testWalletPostFormEmailNotOptionalForAmazonPay' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testPaymentEditNotes' => [
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

    'testPaymentFailedEditNotesMoreThan15Entries' => [
        'request'  => [
            'content' => [
                'notes' => [
                    'key1' => 'value1',
                    'key2' => 'value2',
                    'key3' => 'value3',
                    'key4' => 'value4',
                    'key5' => 'value5',
                    'key6' => 'value6',
                    'key7' => 'value7',
                    'key8' => 'value8',
                    'key9' => 'value9',
                    'key10' => 'value10',
                    'key11' => 'value11',
                    'key12' => 'value12',
                    'key13' => 'value13',
                    'key14' => 'value14',
                    'key15' => 'value15',
                    'key16' => 'value16',
                ],
            ],
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Number of fields in notes should be less than or equal to 15',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_TOO_MANY_KEYS
        ],
    ],

    'testPaymentFailedEditNotesArrayValue' => [
        'request'  => [
            'content' => [
                'notes' => [
                    'key2' => 'new_value',
                    'key3' => ['k' => 'v'],
                ],
            ],
            'method'  => 'PATCH',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Notes values themselves should not be an array',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_NOTES_VALUE_CANNOT_BE_ARRAY
        ],
    ],

    'googlePayPaymentCreateRequestData' => [
        'contact'       => '9876543210',
        'email'         => 'abc@gmail.com',
        'currency'      => 'INR',
        'method'        => 'card',
        'application'   => 'google_pay',
        '_'             => [
            'checkout_id'           => 'BY486x1wJh2nFj',
            'os'                    => 'android',
            'package_name'          => 'com.oyo.consumer',
            'platform'              => 'mobile_sdk',
            'cellular_network_type' => '4G',
            'data_network_type'     => 'cellular',
            'locale'                => 'en-',
            'library'               => 'custom',
            'library_version'       => '3.6.0'
        ],
    ],

    'testCreateGooglePayCardPaymentInvalidCurrency' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Currency is not supported',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_CURRENCY_NOT_SUPPORTED
        ],
    ],

    'testCreateAutoRecurringPaymentBadRequest' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_MERCHANT_RECURRING_PAYMENTS_NOT_SUPPORTED
        ],
    ],

    'testCreateAutoRecurringPaymentBinNotSupported' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_AUTO_RECURRING_NOT_SUPPORTED_ON_IIN
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_AUTO_RECURRING_NOT_SUPPORTED_ON_IIN
        ],
    ],

    'testPaymentS2SJsonPrivateAuthUPIIntent' => [
        'request' => [
            'url' => '/payments/create/json',
            'method' => 'POST',
            'content' => [
                'amount'        => 10000,
                'currency'      => 'INR',
                'contact'       => '9999999999',
                'email'         => 'a@b.com',
                'description'   => 'description',
                'notes'         => [
                    'key'   => 'value'
                ],
                '_'  => [
                    'flow'      => 'intent'
                ],
                'method'        =>  'upi',
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ]
    ],

    'testPaymentS2SJsonPrivateAuthUPIVpa' => [
        'request' => [
            'url' => '/payments/create/json',
            'method' => 'POST',
            'content' => [
                'amount'        => 10000,
                'currency'      => 'INR',
                'contact'       => '9999999999',
                'email'         => 'a@b.com',
                'description'   => 'description',
                'notes'         => [
                    'key'   => 'value'
                ],
                'vpa'           =>  'dontencrypt@icici',
                'method'        =>  'upi',
            ],
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ]
    ],

    'testPaymentMerchantActionWhenNotAuthorized' => [
        'request' => [
            'url'      => '',
            'method'   => 'GET'
        ],
        'response' => [
            'content' => [
               'capture' => false,
                'refund' => false,
            ],
        ]
    ],

    'testPaymentMerchantActionWhenAuthorized' => [
        'request' => [
            'url'      => '',
            'method'   => 'GET'
        ],
        'response' => [
            'content' => [
                'capture' => true,
                'refund' => false,
            ],
        ]
    ],

    'testUpiOtmPaymentFail' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_END_TIME_INVALID,
                ]
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testUpiBlockFail' => [
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The vpa field is not required and not shouldn\'t be sent.'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE'
        ],
    ],

    'testUpiInvalidProvider' => [
        'response' => [
            'content' => [
                'type' => 'async'
            ],
        ],
    ],

    'testUpiOtmWithPastDates' => [
       'response'  => [
           'content' => [
               'error' => [
                   'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                   'description' => PublicErrorDescription::BAD_REQUEST_UPI_MANDATE_END_TIME_INVALID,
               ],
           ],
           'status_code' => 400
       ],
       'exception' => [
           'class'   => RZP\Exception\BadRequestValidationFailureException::class,
           'internal_error_code' => 'BAD_REQUEST_VALIDATION_FAILURE',
       ]
    ],

    'testAutoRefundDisabledPaymentMerchantMail' => [
        'request' => [
            'url' => '/payments/all/reminder',
            'method' => 'GET',
        ],
        'response'  => [
            'content' => [
                'initial' =>  [
                    'counts' =>  [
                        'payments'       =>  1,
                        'merchants'      =>  1,
                        'failures'       =>  0,
                        '10000000000000' =>  1,
                    ]
                ],
                'final' =>  [
                    'counts' =>  [
                        'payments'  =>  0,
                        'merchants' =>  0,
                        'failures'  =>  0,
                        ]
                ]
            ]
        ],
    ],

    'testAutoRefundsPaymentMerchantMail' => [
    'request' => [
        'url' => '/payments/all/reminder',
        'method' => 'GET',
    ],
    'response'  => [
        'content' => [
            'initial' =>  [
                'counts' =>  [
                    'payments'       =>  1,
                    'merchants'      =>  1,
                    'failures'       =>  0,
                    '10000000000000' =>  1,
                ]
            ],
            'final' =>  [
                'counts' =>  [
                    'payments'  =>  0,
                    'merchants' =>  0,
                    'failures'  =>  0,
                ]
            ]
        ]
    ],
],

    'testCreatePaymentWithAmountGreaterThanMaxAmountAndCurrencyUSD' => [
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
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateInternationalPaymentWithAmountGreaterThanMaxAmount' => [
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
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testIntlPaymentWhenNotAllowedForPaymentGateway' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY,
                    'reason' => 'international_transaction_not_allowed',
                    'source' => 'business',
                    'step'   => 'payment_initiation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY
        ],
    ],

    'testIntlPaymentWithOrderIDWhenNotAllowedForPaymentGateway' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY,
                    'reason' => 'international_transaction_not_allowed',
                    'source' => 'business',
                    'step'   => 'payment_initiation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_GATEWAY
        ],
    ],

    'testIntlPaymentWithOrderIDWhenNotAllowedForPaymentLinks' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_LINKS,
                    'reason' => 'international_transaction_not_allowed',
                    'source' => 'business',
                    'step'   => 'payment_initiation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_LINKS
        ],
    ],

    'testIntlPaymentWithOrderIDWhenNotAllowedForPaymentPages' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_PAGES,
                    'reason' => 'international_transaction_not_allowed',
                    'source' => 'business',
                    'step'   => 'payment_initiation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_PAYMENT_PAGES
        ],
    ],

    'testIntlPaymentWithOrderIDWhenNotAllowedForInvoices' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_INVOICES,
                    'reason' => 'international_transaction_not_allowed',
                    'source' => 'business',
                    'step'   => 'payment_initiation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => 'RZP\Exception\BadRequestException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_CARD_INTERNATIONAL_NOT_ALLOWED_FOR_INVOICES
        ],
    ],

    'visaSafeClickPaymentCreateRequestData' => [
        'contact'       => '9000000000',
        'email'         => 'abc@gmail.com',
        'amount'        => '100',
        'currency'      => 'INR',
        'method'        => 'card',
        'application'   => 'visasafeclick',
        'card'          => [
            'number'            => '4012001038443335',
            'name'              => 'Harshil',
            'expiry_month'      => '12',
            'expiry_year'       => '2024',
            'cvv'               => '566',
        ],
        'authentication' => [
            'cavv'                  => '3q2+78r+ur7erb7vyv66vv\/\/8=',
            'cavv_algorithm'        => '1',
            'eci'                   => '05',
            'xid'                   => 'ODUzNTYzOTcwODU5NzY3Qw==',
            'enrolled_status'       => 'Y',
            'authentication_status' => 'Y',
            'provider_data'         => [
                'product_transaction_id'        => '1_156049293_714_62_l73q001m_CHECK211_156049293_714_62_l73q00',
                'product_merchant_reference_id' => '4aa1c9ffd4fc7ded80f73f1d98b35e8e24085404b6e01401',
                'product_type'                  => 'VCIND',
                'auth_type'                     => '3ds'
            ]
        ],
    ],

    'testCreateVisaSafeClickCardS2SPaymentMerchantFeature' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'VisaSafeClick not enabled for merchant.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'test1CCOrderPaymentsWithCustomerDeatils' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ]
    ],

    'test1CCOrderPaymentsWithoutCustomerDeatils' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
          ],
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Something went wrong, please try again after sometime.',
                ],
            ],
            'status_code' => 400,
            'exception' => [
                'class'               => RZP\Exception\BadRequestException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_ERROR
            ],
        ]
    ],

    'testUserConsentPageWithNewCard' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                '_[library]' => 'razorpayjs',
                'save' => '1',
                'method' => 'card',
                'customer_id' => 'cust_100000customer'
            ]
        ]
    ],


    'testUserConsentPageWithNewCardRecurring' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                '_[library]' => 'razorpayjs',
                'recurring' => '1',
                'method' => 'card',
                'customer_id' => 'cust_100000customer'
            ]
        ]
    ],

    'testUserConsentPageWithSavedCardRecurring' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                'recurring' => '1',
                '_[library]' => 'razorpayjs',
                'method' => 'card',
                'customer_id' => 'cust_100000customer',
            ]
        ]
    ],

    'testUserConsentPageWithEmiMethodForNewCard' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                '_[library]' => 'razorpayjs',
                'save' => '1',
                'method' => 'emi',
                'customer_id' => 'cust_100000customer'
            ]
        ]
    ],

    'testUserConsentPageWithSavedCard' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                '_[library]' => 'razorpayjs',
                'method' => 'card',
                'customer_id' => 'cust_100000customer',
            ]
        ]
    ],

    'testUserConsentPageWithEmiMethodForSavedCard' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                '_[library]' => 'razorpayjs',
                'method' => 'emi',
                'customer_id' => 'cust_100000customer',
            ]
        ]
    ],

    'testUserConsentPageForSavedCardWithToken' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                '_[library]' => 'razorpayjs',
                'method' => 'card',
                'customer_id' => 'cust_100000customer',
            ]
        ]
    ],

    'testUserConsentPageForCustomLibrary' => [
        'response' => [
            'input' => [
                'amount' => '50000',
                'currency' => 'INR',
                'contact' => '9918899029',
                '_[library]' => 'custom',
                'method' => 'card',
                'customer_id' => 'cust_100000customer',
            ]
        ]
    ],

    'testCreatePosPayments' => [
        'request'  => [
            'url'       => '/payments/create/pos',
            'method'    => 'POST',
            'headers'   => [
                'x-creator-id'    => '10000000000000',
                'x-creator-type'  => 'merchant'
            ],
            'content'   => [
                'meta'                          => [
                    'reference_id' => '180829064415993E010034214',
                ],
                'status'                        => 'authorized',
                'receiver_type'                 => 'pos',
                'receiver'                      => 'Ezetap',
                'amount'                        => 10000,
                'currency'                      => 'INR',
                "method"                        => "card",
                "card"                          => [
                    "number"      => "4143-66XX-XXXX-1950",
                    "network"     => "VISA",
                    "type"        => "debit"
                ],
                ],
        ],
        'response'  => [
            'status_code' => 200,
            'content'     => [],
        ]
    ],

    'testCreateNBConfig' => [
        'request'  => [
            'url'     => '/netbanking/merchant_configs',
            'method'  => 'post',
            'content' => [
                'merchant_id' => 10000000000000,
                "fields" => [
                    "auto_refund_offset" => 30
                ],
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 200,
        ],
    ],

    'testCreateNBConfigNegative' => [
        'request'  => [
            'url'     => '/netbanking/merchant_configs',
            'method'  => 'post',
            'content' => [
                'merchant_id' => 10000000000000,
                "fields" => [
                    "auto_refund_offset" => 30
                ],
            ]
        ],
        'response' => [
            'content' => [
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_UNAUTHORIZED
        ],
    ],

    'testCheckOfferApplicabilityForPaymentUsingSavedCardWithMappingAvailable' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ]
    ],

    'testCheckOfferApplicabilityForPaymentUsingSavedCardWithMappingUnavailable' => [
        'request' => [
            'url' => '/payments/create/ajax',
            'method' => 'POST',
        ],
        'response' => [
            'content' => [

            ],
            'status_code' => 200,
        ]
    ],

    'testFetchPaymentsCardEntity' => [
        'request' => [
            'method' => 'GET',
            'content' => [
            ]
        ],
        'response' => [
            'content' => [
            ]
        ],
    ],
];

