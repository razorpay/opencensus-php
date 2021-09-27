<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'googlePayProviderPaymentCreateRequestData' => [
        'contact'       => '9876543210',
        'email'         => 'abc@gmail.com',
        'currency'      => 'INR',
        'provider'      => 'google_pay',
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

    'testCreateGooglePaymentWithoutMerchantFeature' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Google Pay not enabled for merchant.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_GOOGLE_PAY_NOT_ENABLED
        ],
    ],

    'testCreateGooglePaymentWithMethod' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid payment method given: upi. Method should not be passed for payments where provider is google_pay',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateGooglePaymentWithAmountGreaterThanMaxAmount' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Amount cannot be greater than ₹200000.00',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testCreateGooglePaymentNoTerminal' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::SERVER_ERROR,
                    'description' => 'We are facing some trouble completing your request at the moment. Please try again shortly.',
                ],
            ],
            'status_code' => 500,
        ],
        'exception' => [
            'class'               => RZP\Exception\ServerErrorException::class,
            'internal_error_code' => ErrorCode::SERVER_ERROR_NO_TERMINAL_FOUND
        ],
    ],
];

