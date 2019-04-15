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
                        'description' => 'The amount must be atleast 14',
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

    'testCreatePaymentWithDisabledMethod' => [
        'response' => [
            'content' => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => PublicErrorDescription::BAD_REQUEST_PAYMENT_NETBANKING_NOT_ENABLED_FOR_MERCHANT,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class' => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_NETBANKING_NOT_ENABLED_FOR_MERCHANT
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
];
