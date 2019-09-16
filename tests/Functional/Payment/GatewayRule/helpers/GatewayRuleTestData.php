<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testCreateAuthenticationGatewayRule' => [
        [
            'request' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'filter',
                    'filter_type'   => 'select',
                    'min_amount'    => 0,
                    'group'         => 'authentication',
                    'auth_type'     => '3ds',
                    'step'          => 'authentication',
                    'authentication_gateway' => 'mpi_blade',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'filter',
                    'filter_type'   => 'select',
                    'min_amount'    => 0,
                    'group'         => 'authentication',
                    'auth_type'     => '3ds',
                    'step'          => 'authentication',
                    'authentication_gateway' => 'mpi_blade',
                ],
            ],
        ],
        [
           'request' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'sorter',
                    'load'          => 100,
                    'group'         => 'authentication',
                    'auth_type'     => '3ds',
                    'authentication_gateway' => 'mpi_blade',
                    'step'          => 'authentication',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                   'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'sorter',
                    'load'          => 100,
                    'group'         => 'authentication',
                    'auth_type'     => '3ds',
                    'authentication_gateway' => 'mpi_blade',
                    'step'          => 'authentication',
                ],
            ],
        ],
        [
           'request' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'filter',
                    'filter_type'   => 'select',
                    'min_amount'    => 0,
                    'group'         => 'authentication',
                    'auth_type'     => 'headless_otp',
                    'network'       => 'MC',
                    'step'          => 'authentication',
                    'authentication_gateway' => 'mpi_blade',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                   'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'filter',
                    'filter_type'   => 'select',
                    'min_amount'    => 0,
                    'group'         => 'authentication',
                    'auth_type'     => 'headless_otp',
                    'network'       => 'MC',
                    'step'          => 'authentication',
                    'authentication_gateway' => 'mpi_blade',

                ],
            ],
        ],
        [
           'request' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'sorter',
                    'load'          => 50,
                    'group'         => 'authentication',
                    'auth_type'     => 'headless_otp',
                    'authentication_gateway' => 'mpi_blade',
                    'step'          => 'authentication',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'cybersource',
                    'type'          => 'sorter',
                    'load'          => 50,
                    'group'         => 'authentication',
                    'auth_type'     => 'headless_otp',
                    'authentication_gateway' => 'mpi_blade',
                    'step'          => 'authentication',

                ],
            ],
        ],
        [
           'request' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'first_data',
                    'type'          => 'sorter',
                    'load'          => 50,
                    'group'         => 'authentication',
                    'auth_type'     => 'headless_otp',
                    'authentication_gateway' => 'mpi_blade',
                    'step'          => 'authentication',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'method'        => 'card',
                    'merchant_id'   => '100000Razorpay',
                    'gateway'       => 'first_data',
                    'type'          => 'sorter',
                    'load'          => 50,
                    'group'         => 'authentication',
                    'auth_type'     => 'headless_otp',
                    'authentication_gateway' => 'mpi_blade',
                    'step'          => 'authentication',

                ],
            ],
        ]
    ],

    'testCreateGatewayRule' => [
        // Create recurring type gateway rule for card
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'select',
                    'group'            => 'test',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'method_type'      => 'credit',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'min_amount'       => 100,
                    'max_amount'       => 500,
                    'category2'        => 'ecommerce',
                    'network_category' => 'ecommerce',
                    'shared_terminal'  => 1,
                    'gateway_acquirer' => 'axis',
                    'recurring'        => true,
                    'recurring_type'   => 'auto',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'select',
                    'group'            => 'test',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'method_type'      => 'credit',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => false,
                    'min_amount'       => 1,
                    'max_amount'       => 5,
                    'category2'        => 'ecommerce',
                    'network_category' => 'ecommerce',
                    'shared_terminal'  => true,
                    'gateway_acquirer' => 'axis',
                    'admin'            => true,
                    'recurring'        => true,
                    'recurring_type'   => 'auto',
                ],
            ],
        ],
        // Create sorter gateway rule for card
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'currency'         => 'INR',
                    'international'    => 0,
                    'load'             => 50,
                    'comments'         => 'some comments',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'currency'         => 'INR',
                    'international'    => false,
                    'min_amount'       => 0,
                    'load'             => 50,
                    'comments'         => 'some comments',
                    'admin'            => true
                ],
            ],
        ],
        // Create sorter gateway rule for card with min_amount and max_amount
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'min_amount'       => 100,
                    'max_amount'       => 500,
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => false,
                    'min_amount'       => 1,
                    'max_amount'       => 5,
                    'load'             => 50,
                    'admin'            => true
                ],
            ],
        ],
        // Create sorter rule with invalid gateway
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'xyz',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Gateway is invalid',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule with invalid gateway for method
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'netbanking_hdfc',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Gateway netbanking_hdfc does not support card method',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule with invalid payment method
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'hdfc',
                    'method'           => 'xyz',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'xyz is not a valid payment method',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule with invalid network
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'hdfc',
                    'method'           => 'card',
                    'network'          => 'xyz',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'XYZ is not a valid network',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule with unsupported network for gateway
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'network'          => 'DICL',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'DICL is not a valid network for gateway axis_migs',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule with invalid bank issuer code
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'hdfc',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'XYZ',
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'XYZ is not a valid bank code',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule with invalid method_type
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'hdfc',
                    'method'           => 'card',
                    'method_type'      => 'xyz',
                    'network'          => 'VISA',
                    'issuer'           => 'ICIC',
                    'international'    => 0,
                    'load'             => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Card Type: xyz is not supported',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule with invalid acquirer for gateway
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'select',
                    'gateway'          => 'hdfc',
                    'method'           => 'card',
                    'network'          => 'VISA',
                    'issuer'           => 'ICIC',
                    'international'    => 0,
                    'gateway_acquirer' => 'axis',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'axis is not a valid gateway acquirer for hdfc',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create sorter rule for netbanking
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'billdesk',
                    'method'      => 'netbanking',
                    'issuer'      => 'SBIN',
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'sorter',
                    'gateway'          => 'billdesk',
                    'method'           => 'netbanking',
                    'issuer'           => 'SBIN',
                    'load'             => 50,
                    'admin'            => true
                ],
            ],
        ],
        // Creeate rule with direct netbanking gateway and null issuer
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'netbanking_hdfc',
                    'method'      => 'netbanking',
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'issuer can be null only for shared netbanking gateways',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create netbanking rule with unsupported bank for gateway
        [
            'request' => [
                'content' => [
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'ebs',
                    'issuer'      => 'ALLA',
                    'method'      => 'netbanking',
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'ALLA is not a supported bank for gateway ebs',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create rulw with min_amount > max_amount
        [
            'request' => [
                'content' => [
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 500,
                    'max_amount'  => 100,
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'min_amount should be lesser than max_amount',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        [
            'request' => [
                'content' => [
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 500,
                    'max_amount'  => 100,
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'min_amount should be lesser than max_amount',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create wallet sorter rule
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'wallet_jiomoney',
                    'issuer'      => 'jiomoney',
                    'method'      => 'wallet',
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'wallet_jiomoney',
                    'method'      => 'wallet',
                    'load'        => 50,
                    'admin'       => true,
                ],
            ],
        ],
        // Create sorter rule with null gateway
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'method'      => 'card',
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'The gateway field is required when type is sorter.',
                    ],
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // test create sorter rule with total load less than 100
        [
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'network'     => null,
                    'min_amount'  => 0,
                    'load'        => 60
                ]
            ],
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'network'     => 'VISA',
                    'load'        => 40
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'network'     => 'VISA',
                    'load'        => 40,
                    'admin'       => true
                ],
            ],
        ],
        // create sorter rule with total load greater than 100
        [
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'network'     => 'VISA',
                    'min_amount'  => 0,
                    'load'        => 60
                ],
            ],
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'network'     => 'VISA',
                    'load'        => 50
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Load across all gateway rules must be less than 100 percent',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create more specific rule while more generic rule exists
        [
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '100000Razorpay',
                    'gateway'     => 'hdfc',
                    'network'     => null,
                    'min_amount'  => 0,
                    'load'        => 90
                ]
            ],
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'iins'        => ['411111'],
                    'load'        => 90
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'iins'        => ['411111'],
                    'load'        => 90,
                    'admin'       => true
                ],
            ],
        ],
        // Create generic rule while more specific rule exists
        [
            'fixtures' => [
                [
                    'merchant_id' => '10000000000000',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'iins'        => ['411111'],
                    'load'        => 90,
                ]
            ],
            'request' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '100000Razorpay',
                    'gateway'     => 'hdfc',
                    'load'        => 90,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'type'        => 'sorter',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'load'        => 90,
                    'admin'       => true
                ],
            ],
        ],
        // Create select type filter rule
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'select',
                    'group'            => 'test',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'method_type'      => 'credit',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'min_amount'       => 100,
                    'max_amount'       => 500,
                    'category2'        => 'ecommerce',
                    'network_category' => 'ecommerce',
                    'shared_terminal'  => 1,
                    'gateway_acquirer' => 'axis',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'select',
                    'group'            => 'test',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'method_type'      => 'credit',
                    'network'          => 'VISA',
                    'issuer'           => 'HDFC',
                    'international'    => false,
                    'min_amount'       => 1,
                    'max_amount'       => 5,
                    'category2'        => 'ecommerce',
                    'network_category' => 'ecommerce',
                    'shared_terminal'  => true,
                    'gateway_acquirer' => 'axis',
                    'admin'            => true
                ],
            ],
        ],
        // Create reject type filter rule with unsupported network for gateway
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'reject',
                    'group'            => 'test',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'method_type'      => 'credit',
                    'network'          => 'DICL',
                    'issuer'           => 'HDFC',
                    'international'    => 0,
                    'min_amount'       => 100,
                    'max_amount'       => 500,
                    'category2'        => 'ecommerce',
                    'network_category' => 'ecommerce',
                    'shared_terminal'  => 1,
                    'gateway_acquirer' => 'axis',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'reject',
                    'group'            => 'test',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'method_type'      => 'credit',
                    'network'          => 'DICL',
                    'issuer'           => 'HDFC',
                    'international'    => false,
                    'min_amount'       => 1,
                    'max_amount'       => 5,
                    'category2'        => 'ecommerce',
                    'network_category' => 'ecommerce',
                    'shared_terminal'  => true,
                    'gateway_acquirer' => 'axis',
                    'admin'            => true
                ],
            ],
        ],
        // Create filter rule with null gateway
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'reject',
                    'group'            => 'test',
                    'method'           => 'card',
                    'issuer'           => 'HDFC',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'reject',
                    'group'            => 'test',
                    'method'           => 'card',
                    'issuer'           => 'HDFC',
                    'admin'            => true
                ],
            ],
        ],
        // Create filter rule with iin
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'test',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'iins'        => ['411111'],
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'test',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'iins'        => ['411111'],
                    'admin'       => true,
                ],
            ],
        ],
        // Create filter rule with upi
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'test',
                    'gateway'     => 'upi_mindgate',
                    'method'      => 'upi',
                    'issuer'      => 'ICIC',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'test',
                    'gateway'     => 'upi_mindgate',
                    'method'      => 'upi',
                    'issuer'      => 'ICIC',
                    'admin'       => true,
                ],
            ],
        ],
        // Create filter rule with invalid issuer
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'test',
                    'gateway'     => 'upi_mindgate',
                    'method'      => 'upi',
                    'issuer'      => 'abcd',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Invalid bank code for PSP',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create rule with invalid array format for iins
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'test',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'iins'        => ['a' => '411111'],
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'iins should be sent as a numerically indexed array',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create rule with invalid iin length
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'test',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'iins'        => ['41111'],
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'iins should be equal to 6 characters',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create rule with invalid merchant category2
        [
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'test',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'category2'   => 'xyz',
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Category: xyz invalid for merchant',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Create rule with invalid terminal network_category
        [
            'request' => [
                'content' => [
                    'merchant_id'      => '10000000000000',
                    'type'             => 'filter',
                    'filter_type'      => 'select',
                    'group'            => 'test',
                    'gateway'          => 'axis_migs',
                    'method'           => 'card',
                    'network_category' => 'retail_services'
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Category provided invalid for gateway',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Test adding select and reject filter for same criteria in same group
        [
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'groupA',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'min_amount'  => 100,
                    'max_amount'  => 700,
                ]
            ],
            'request' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupA',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 200,
                    'max_amount'  => 500,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'select and reject filter rules for same criteria cannot be present in same group',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Test adding select and reject filter for same criteria in same group for different merchants
        [
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'groupA',
                    'merchant_id' => '1ApiFeeAccount',
                    'gateway'     => 'hdfc',
                    'min_amount'  => 100,
                    'max_amount'  => 700,
                ]
            ],
            'request' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupA',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 200,
                    'max_amount'  => 500,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '10000000000000',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupA',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 2,
                    'max_amount'  => 5,
                ],
            ],
        ],
        // test adding select / reject rules for same criteria but in different groups
        [
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'groupA',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'min_amount'  => 100,
                    'max_amount'  => 700,
                ]
            ],
            'request' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupB',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 200,
                    'max_amount'  => 500,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupB',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 2,
                    'max_amount'  => 5,
                ],
            ],
        ],
        // test adding rules with overlapping iins
        [
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'filter',
                    'filter_type' => 'select',
                    'group'       => 'groupA',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'min_amount'  => 0,
                    'iins'        => ['411111'],
                ]
            ],
            'request' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupA',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 0,
                    'iins'        => ['401201', '411111'],
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'select and reject filter rules for same criteria cannot be present in same group',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // Test for creating filter gateway rule with procurer
        [

            'request' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'procurer'    => 'merchant',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupA',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 300,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'procurer'    => 'merchant',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupA',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'min_amount'  => 3,
                ],
            ],
        ],
        // Test for creating sorter gateway rule with procurer
        [

            'request' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'procurer'    => 'merchant',
                    'merchant_id' => '100000Razorpay',
                    'gateway'     => 'hdfc',
                    'load'        => 90,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'procurer'    => 'merchant',
                    'type'        => 'sorter',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'load'        => 90,
                ],
            ],
        ],
        // Create a sorter rule with equal load with different procurer
        [
            'fixtures' => [
                [
                    'merchant_id' => '100000Razorpay',
                    'procurer'    => 'razorpay',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'load'        => 90,
                ]
            ],
            'request' => [
                'content' => [
                    'method'      => 'card',
                    'procurer'    => 'merchant',
                    'type'        => 'sorter',
                    'merchant_id' => '100000Razorpay',
                    'gateway'     => 'hdfc',
                    'load'        => 90,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'merchant_id' => '100000Razorpay',
                    'procurer'    => 'merchant',
                    'type'        => 'sorter',
                    'gateway'     => 'hdfc',
                    'method'      => 'card',
                    'load'        => 90,
                    'admin'       => true
                ],
            ],
        ],
        // Create a sorter rule with same load with same procurer
        [
            'fixtures' => [
                [
                    'merchant_id' => '100000Razorpay',
                    'procurer'    => 'merchant',
                    'type'        => 'sorter',
                    'gateway'     => 'axis_migs',
                    'method'      => 'card',
                    'load'        => 90,
                ]
            ],
            'request' => [
                'content' => [
                    'method'      => 'card',
                    'procurer'    => 'merchant',
                    'type'        => 'sorter',
                    'merchant_id' => '100000Razorpay',
                    'gateway'     => 'hdfc',
                    'load'        => 90,
                ],
                'url' => '/gateway/rules',
                'method' => 'POST',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Load across all gateway rules must be less than 100 percent',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
    ],

    'testUpdateGatewayRule' => [
        // test update sorter rule load
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'sorter',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
                'load'        => 50
            ],
            'request' => [
                'content' => [
                    'load'     => 70,
                    'group'    => 'groupA',
                    'comments' => 'some comments',
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'group'       => 'groupA',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'network'     => 'VISA',
                    'min_amount'  => 0,
                    'load'        => 70,
                    'comments'    => 'some comments',
                ]
            ]
        ],
        // test update load for filter rule
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'filter',
                'filter_type' => 'select',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
            ],
            'request' => [
                'content' => [
                    'load'  => 70,
                    'group' => 'groupA',
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'load is editable only for sorter rules',
                    ]
                ],
                'status_code' => 400,
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // test update load for specific rule while generic rule exists
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'sorter',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
                'load'        => 50
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'axis_migs',
                    'min_amount'  => 0,
                    'load'        => 50
                ]
            ],
            'request' => [
                'content' => [
                    'load'  => 70,
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'network'     => 'VISA',
                    'min_amount'  => 0,
                    'load'        => 70,
                ],
            ],
        ],
        // test update generic rule while more generic rule exists
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'sorter',
                'merchant_id' => '100000Razorpay',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
                'load'        => 50
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'axis_migs',
                    'min_amount'  => 0,
                    'load'        => 50
                ]
            ],
            'request' => [
                'content' => [
                    'load'  => 70,
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '100000Razorpay',
                    'gateway'     => 'hdfc',
                    'network'     => 'VISA',
                    'min_amount'  => 0,
                    'load'        => 70,
                ],
            ],
        ],
        // test update sorter rule load, but total load will exceed 100 on update
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'sorter',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
                'load'        => 50
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'axis_migs',
                    'network'     => 'VISA',
                    'min_amount'  => 0,
                    'load'        => 50
                ]
            ],
            'request' => [
                'content' => [
                    'load'  => 70,
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'Load across all gateway rules must be less than 100 percent',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // test update rule_type for recurring_type filter rule
        [
            'to_update' => [
                'method'            => 'card',
                'type'              => 'filter',
                'filter_type'       => 'select',
                'group'             => 'groupB',
                'merchant_id'       => '10000000000000',
                'gateway'           => 'hdfc',
                'network'           => 'VISA',
                'min_amount'        => 0,
                'recurring'         => true,
                'recurring_type'    => 'initial',
            ],
            'request' => [
                'content' => [
                    'filter_type' => 'reject',
                    'group'       => 'groupB',
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'method'            => 'card',
                    'type'              => 'filter',
                    'filter_type'       => 'reject',
                    'group'             => 'groupB',
                    'merchant_id'       => '10000000000000',
                    'gateway'           => 'hdfc',
                    'network'           => 'VISA',
                    'min_amount'        => 0,
                    'recurring'         => true,
                    'recurring_type'    => 'initial',
                ]
            ]
        ],
        // test update rule_type for filter rule
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'filter',
                'filter_type' => 'select',
                'group'       => 'groupB',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
            ],
            'request' => [
                'content' => [
                    'filter_type' => 'reject',
                    'group'       => 'groupB',
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'group'       => 'groupB',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'network'     => 'VISA',
                    'min_amount'  => 0,
                ]
            ]
        ],
        // test update rule_type for filter rule but select and reject rules will be in same group on update
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'filter',
                'filter_type' => 'reject',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
            ],
            'fixtures' => [
                [
                    'method'      => 'card',
                    'type'        => 'filter',
                    'filter_type' => 'reject',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'min_amount'  => 0,
                ]
            ],
            'request' => [
                'content' => [
                    'filter_type' => 'select'
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'select and reject filter rules for same criteria cannot be present in same group',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // test update filter_type for sorter rule
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'sorter',
                'group'       => 'groupB',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'network'     => 'VISA',
                'min_amount'  => 0,
                'load'        => 50,
            ],
            'request' => [
                'content' => [
                    'filter_type' => 'reject',
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'filter_type is editable only for filter rules',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ],
        // test edit iin for rule
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'sorter',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'min_amount'  => 0,
                'load'        => 50
            ],
            'request' => [
                'content' => [
                    'iins' => ['411111'],
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'min_amount'  => 0,
                    'iins'        => ['411111'],
                    'load'        => 50,
                ]
            ]
        ],
        //test update iin for existing rule with iin
        [
            'to_update' => [
                'method'      => 'card',
                'type'        => 'sorter',
                'merchant_id' => '10000000000000',
                'gateway'     => 'hdfc',
                'iins'        => ['411111'],
                'min_amount'  => 0,
                'load'        => 50
            ],
            'request' => [
                'content' => [
                    'iins' => ['401201'],
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'method'      => 'card',
                    'type'        => 'sorter',
                    'merchant_id' => '10000000000000',
                    'gateway'     => 'hdfc',
                    'min_amount'  => 0,
                    'iins'        => ['401201'],
                    'load'        => 50,
                ]
            ]
        ],
        // test edit iin for netbanking rule
        [
            'to_update' => [
                'method'      => 'netbanking',
                'type'        => 'sorter',
                'merchant_id' => '10000000000000',
                'gateway'     => 'billdesk',
                'min_amount'  => 0,
                'load'        => 50
            ],
            'request' => [
                'content' => [
                    'iins' => ['411111'],
                ],
                'method' => 'PATCH',
            ],
            'response' => [
                'content' => [
                    'error' => [
                        'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                        'description' => 'iins should be sent only for card or emi rules',
                    ]
                ],
                'status_code' => 400
            ],
            'exception' => [
                'class'               => \RZP\Exception\BadRequestValidationFailureException::class,
                'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
            ],
        ]
    ],

    'testDeleteGatewayRule' => [
        'request' => [
            'method' => 'DELETE'
        ],
        'response' => [
            'content' => [
                'deleted' => true,
            ],
        ],
    ],
];
