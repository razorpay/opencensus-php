<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

return [
    'testAddIin' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'RuPay',
                'type'      => 'debit',
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'RuPay',
                'type'      => 'debit',
                'recurring' => false,
            ],
        ],
    ],

    'testAddIinWithSubType'  => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'RuPay',
                'type'      => 'debit',
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'RuPay',
                'type'      => 'debit',
                'sub_type'  => 'consumer',
                'recurring' => false,
            ],
        ],
    ],

    'testAddIinWithInValidSubType'  => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'RuPay',
                'sub_type'  => 'abc',
                'type'      => 'debit',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid sub_type: abc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testAddIinWithRecurring' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'RuPay',
                'type'      => 'debit',
                'sub_type'  => 'consumer',
                'recurring' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'RuPay',
                'type'      => 'debit',
                'sub_type'  => 'consumer',
                'recurring' => true,
            ],
        ],
    ],

    'testAddIinWithCountry' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'           => 112333,
                'network'       => 'RuPay',
                'type'          => 'debit',
                'country'       => 'US',
            ],
        ],
        'response' => [
            'content' => [
                'iin'           => '112333',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'recurring'     => false,
                'country'       => 'US',
            ],
        ],
    ],

    'testAddIinWithCategory' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'Visa',
                'type'      => 'credit',
                'category'  => 'Signature',
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'Visa',
                'type'      => 'credit',
                'sub_type'  => 'consumer',
                'category'  => 'Signature',
            ],
        ],
    ],

    'testAddIinWithCategoryAndRuPay' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'RuPay',
                'type'      => 'credit',
                'category'  => 'abc',
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'RuPay',
                'type'      => 'credit',
                'sub_type'  => 'consumer',
                'category'  => 'abc',
            ],
        ],
    ],

    'testAddIinWithMandateHub' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'          => 112333,
                'network'      => 'RuPay',
                'type'         => 'debit',
                'mandate_hubs' => [
                    'mandate_hq' => '1',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'RuPay',
                'type'      => 'debit',
                'recurring' => false,
                'mandate_hubs' => [
                    'mandate_hq'
                ],
            ],
        ],
    ],

    'testAddIinWithInvalidMandateHub' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'          => 112333,
                'network'      => 'RuPay',
                'type'         => 'debit',
                'mandate_hubs' => [
                    'invalid_hub' => '1',
                ],
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid mandate_hub in input: invalid_hub',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testEditIinWithMandateHub' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'put',
            'content' => [
                'mandate_hubs'      => [
                    'billdesk_sihub' => '1',
                ],
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'RuPay',
                'mandate_hubs'      => [
                    'mandate_hq',
                    'billdesk_sihub',
                ],
            ],
        ],
    ],

    'testEditIinWithoutCategory' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'put',
            'content' => [
                'type'      => 'credit',
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'network'   => 'RuPay',
                'type'      => 'credit',
                'sub_type'  => 'consumer',
            ],
        ],
    ],

    'testAddIinWithInvalidCategory' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'Visa',
                'type'      => 'credit',
                'sub_type'  => 'consumer',
                'category'  => 'abc',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Not a valid category: abc',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testEditIinWithCategoryWithoutNetwork' => [
        'request' => [
            'url'     => '/iins/112333',
            'method'  => 'put',
            'content' => [
                'type'      => 'credit',
                'sub_type'  => 'consumer',
                'category'  => 'abc',
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The network field is required when category is present.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ]
    ],

    'testGetPaymentFlows' => [
        'request'  => [
            'url'     => '/payment/flows',
            'content' => [
                'iin' => '112333',
            ],
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'pin'       => true,
                'otp'       => true,
                'recurring' => false,
                'iframe'    => true,
            ],
        ],
    ],

    'testGetPaymentFlowsEmptyResponse' => [
        'request'  => [
            'url'     => '/payment/flows',
            'content' => [
                'iin' => '112333',
            ],
            'method'  => 'get',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetPaymentOtpFlow' => [
        'request'  => [
            'url'     => '/payment/flows',
            'content' => [
                'iin' => '112333',
            ],
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'otp' => true,
            ],
        ],
    ],

    'testGetPaymentFlowsFromIinDetailsEndpoint' => [
        'request'  => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '112333',
            ],
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'flows' => [
                    'pin'       => true,
                    'otp'       => true,
                    'recurring' => false,
                    'iframe'    => true,
                ],
            ],
        ],
    ],

    'testCobrandingPartnerFromIinDetailsEndpoint' => [
        'request'  => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '112333',
            ],
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'cobranding_partner' => 'onecard',
            ],
        ],
    ],

    'testGetPaymentFlowsEmptyResponseFromIinDetailsEndpoint' => [
        'request'  => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '112333',
            ],
            'method'  => 'get',
        ],
        'response' => [
            'content' => [],
        ],
    ],

    'testGetPaymentOtpFlowFromIinDetailsEndpoint' => [
        'request'  => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '112333',
            ],
            'method'  => 'get',
        ],
        'response' => [
            'content' => [
                'flows'   => [
                    'otp' => true,
                ],
            ],
        ],
    ],


    'testEditIinFailedInvalidMessageType' => [
        'request'     => [
            'url'     => '/iins/112333',
            'method'  => 'put',
            'content' => [
                'country'        => 'IN',
                'emi'            => 1,
                'network'        => 'RuPay',
                'type'           => 'credit',
                'message_type'   => 'ABC'
            ],
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'Invalid Message type given',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testEditIin' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'put',
            'content' => [
                'country'        => 'IN',
                'issuer'         => 'HDFC',
                'issuer_name'    => 'HDFC',
                'emi'            => 1,
                'network'        => 'RuPay',
                'type'           => 'credit',
                'message_type'   => 'SMS',
            ],
        ],
        'response' => [
            'content' => [
                'iin'            => '112333',
                'network'        => 'RuPay',
                'type'           => 'credit',
                'country'        => 'IN',
                'issuer'         => 'HDFC',
                'issuer_name'    => 'HDFC Bank',
                'emi'            => true,
                'message_type'   => 'SMS',
                'recurring'      => false,
            ],
        ],
    ],

    'testLockedIin' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'put',
            'content' => [
                'locked' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'locked' => true,
            ],
        ],
    ],

    'testGetIin' => [
        'request' => [
            'url' => '/admin/iin/607500',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'           => '607500',
                'category'      => 'STANDARD',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer'        => 'SBIN',
                'issuer_name'   => 'State Bank of India',
                'trivia'        => 'random trivia'
            ]
        ],
    ],

    'testPrivateGetIin' => [
        'request' => [
            'url' => '/iins/607500',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'           => '607500',
                'entity'        => 'iin',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'sub_type'      => 'consumer',
                'issuer_code'   => 'SBIN',
                'issuer_name'   => 'State Bank of India',
                'international' => false,
                'emi' => [
                    'available' => false,
                ],
                'recurring' => [
                    'available' => false,
                ],
                'authentication_types' => [
                    [
                        'type' => '3ds'
                    ],
                ]
            ]
        ],
    ],

    'testPrivateGetIinWithoutFeatureFlag' => [
        'request' => [
            'url' => '/iins/607500',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The requested URL was not found on the server.',
                ],
            ],
            'status_code' => 400,
        ],
    ],

    'testPrivateGetIinInternational' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'           => '112333',
                'entity'        => 'iin',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'sub_type'      => 'consumer',
                'issuer_code'   => 'unknown',
                'issuer_name'   => 'unknown',
                'international' => true,
                'emi' => [
                    'available' => false,
                ],
                'recurring' => [
                    'available' => false,
                ],
                'authentication_types' => [
                    [
                        'type' => '3ds'
                    ],
                ]
            ]
        ],
    ],

    'testPrivateGetIinNotPresent' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => PublicErrorDescription::BAD_REQUEST_IIN_NOT_EXISTS,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => 'RZP\Exception\BadRequestException',
            'internal_error_code'   => ErrorCode::BAD_REQUEST_IIN_NOT_EXISTS,
        ],
    ],

    'testPrivateGetInvalidIin' => [
        'request' => [
            'url' => '/iins/abcd',
            'method' => 'get',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The iin must be a number.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testPrivateGetIinAllFlowsSupported' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'           => '112333',
                'entity'        => 'iin',
                'network'       => 'RuPay',
                'type'          => 'credit',
                'sub_type'      => 'consumer',
                'issuer_code'   => 'unknown',
                'issuer_name'   => 'unknown',
                'international' => true,
                'emi' => [
                    'available' => true,
                ],
                'recurring' => [
                    'available' => true,
                ],
                'authentication_types' => [
                    [
                        'type' => '3ds'
                    ],
                    [
                        'type' => 'otp'
                    ],
                ]
            ]
        ],
    ],

    'testPrivateGetIinIvrSupported' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'           => '112333',
                'entity'        => 'iin',
                'network'       => 'RuPay',
                'type'          => 'credit',
                'sub_type'      => 'consumer',
                'issuer_code'   => 'unknown',
                'issuer_name'   => 'unknown',
                'international' => true,
                'emi' => [
                    'available' => true,
                ],
                'recurring' => [
                    'available' => true,
                ],
                'authentication_types' => [
                    [
                        'type' => '3ds'
                    ],
                    [
                        'type' => 'otp'
                    ],
                ]
            ]
        ],
    ],

    'testGetIinDCCBlacklistedUpdate' => [
        'request' => [
            'url'     => '/iins/bulk',
            'method'  => 'PATCH',
            'content' => [
                'iins'   => ['401200', '401201'],
                'payload' => [
                    'flows' => [
                        'dcc_blacklisted' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                '401200' => [
                    'flows' => [
                        '3ds',
                        'dcc_blacklisted',
                    ],
                ],
                '401201' => [
                    'flows' => [
                        '3ds',
                        'dcc_blacklisted',
                    ],
                ],
            ],
        ],
    ],

    'testPrivateGetIinEmptyNetwork' => [
        'request' => [
            'url' => '/iins/112333',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'iin'           => '112333',
                'entity'        => 'iin',
                'network'       => 'Unknown',
                'type'          => 'credit',
                'sub_type'      => 'consumer',
                'issuer_code'   => 'unknown',
                'issuer_name'   => 'unknown',
                'international' => true,
                'emi' => [
                    'available' => true,
                ],
                'recurring' => [
                    'available' => true,
                ],
                'authentication_types' => [
                    [
                        'type' => '3ds'
                    ],
                ]
            ]
        ],
    ],

    'testBatchServiceIinUpdate' => [
        'request' => [
            'url' => '/iins/iin_npci_rupay/process',
            'method' => 'post',
            'content' => [
                [
                    'row'   => 'ABHY065000160726100060726199916S010101E&M01D356IN140513000000N',
                    'idempotent_id' => 'batch_abc123'
                ]
            ],
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 1,
                'items' => [
                    [
                        'batch_id'        => 'C0zv9I46W4wiOq',
                        'idempotent_id'   => 'batch_abc123',
                        'status'          => 1,
                    ]
                ],
            ],
        ],
    ],


    'testGetIins' => [
        'request' => [
            'url' => '/admin/iin',
            'method' => 'get',
        ],
        'response' => [
            'content' => [
                'entity' => 'collection',
                'count' => 28,
                'items' => [
                    [
                    ]
                ]
            ]
        ],
    ],

    'testImportIin' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'files' => [
                'file' => '',
            ],
            'content' => [
                'network' => 'MasterCard',
            ],
        ],
        'response' => [
            'content' => [
                'duplicates' => [],
                'db_conflicts' => [],
                'network_errors' => [
                    '497522' => [
                        8,
                    ]
                ],
                'success' => 5,
            ],
        ],
    ],

    'testImportIinWithMessageType' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'files' => [
                'file' => '',
            ],
            'content' => [
                'network' => 'MasterCard',
            ],
        ],
        'response' => [
            'content' => [
                'duplicates' => [],
                'db_conflicts' => [],
                'network_errors' => [
                    '497522' => [
                        8,
                    ]
                ],
                'success' => 5,
            ],
        ],
    ],

    'testImportIinWithIssuer' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'files' => [
                'file' => '',
            ],
            'content' => [
                'network' => 'MasterCard',
            ],
        ],
        'response' => [
            'content' => [
                'duplicates'  => [
                ],
                'db_conflicts' => [
                ],
                'network_errors' => [
                    '497522' => [
                        8,
                    ]
                ],
                'success' => 5,
            ],
        ],
    ],

    'testIinRangeUploadWithType' => [
        'request' => [
            'url' => '/iins/range/upload',
            'method' => 'post',
            'content' => [
                'min' => 652850,
                'max' => 652855,
                'network' => 'RuPay',
                'type' => 'credit',
                'country' => 'IN'
            ]
        ],
        'response' => [
            'content' => [
                'success' => 6,
            ],
        ],
    ],

    'testGetCardPaymentFlowsFailure' => [
        'request'  => [
            'url'     => '/payment/flows',
            'content' => [
                'card_number' => '42123012001036275556342',
            ],
            'method'  => 'post',
        ],
        'response'  => [
            'content'     => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The card number is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testGetCardPaymentFlowsEmpty' => [
        'request'  => [
            'url'     => '/payment/flows',
            'content' => [
                'card_number' => '4012001036275556',
            ],
            'method'  => 'post',
        ],
        'response'  => [
            'content'     => [
            ],
        ],
    ],

    'testGetCardPaymentFlowsFromIin' => [
        'request'  => [
            'url'     => '/payment/flows',
            'content' => [
                'iin' => '401200',
            ],
            'method'  => 'post',
        ],
        'response'  => [
            'content' => [
                'pin' => true,
                'otp' => true,
            ],
        ],
    ],

    'testGetCardPaymentFlowAndIinDetails' => [
        'request' => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '401200',
            ],
            'method'  => 'get'
        ],
        'response' => [
            'content'     => [
                'flows'   => [
                    'recurring' => true,
                    'iframe'    => false,
                    'emi'       => true,
                ],
                'issuer'  => 'HDFC',
                'type'    => 'credit',
            ]
        ]
    ],

    'testGetCardPaymentDomesticIinNA' => [
        'request' => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '401200',
            ],
            'method'  => 'get'
        ],
        'response' => [
            'content'     => [
                'flows'   => [
                    'recurring' => false,
                    'iframe'    => false,
                    'emi'       => true,
                ],
                'issuer'  => 'HDFC',
                'type'    => 'credit',
            ]
        ]
    ],

    'testGetCardPaymentDomesticIinTestMode' => [
        'request' => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '401200',
            ],
            'method'  => 'get'
        ],
        'response' => [
            'content'     => [
                'flows'   => [
                    'recurring' => true,
                    'iframe'    => false,
                    'emi'       => true,
                ],
                'issuer'  => 'HDFC',
                'type'    => 'credit',
            ]
        ]
    ],

    'testGetCardPaymentDomesticIinLiveMode' => [
        'request' => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '401200',
            ],
            'method'  => 'get'
        ],
        'response' => [
            'content'     => [
                'flows'   => [
                    'recurring' => true,
                    'iframe'    => false,
                    'emi'       => true,
                ],
                'issuer'  => 'HDFC',
                'type'    => 'credit',
            ]
        ]
    ],

    'testGetIssuerDetails' => [
        'request' => [
            'url'     => '/mandate_hq/iin/401200',
            'content' => [],
            'method'  => 'get'
        ],
        'response' => [
            'content'     => [
                'issuer'      => 'HDFC',
                'issuer_name' => 'HDFC Bank',
                'network'     => 'Visa'
            ]
        ]
    ],

    'testGetCardPaymentFlowAndIinDetailsWithEmiNotEnabled' => [
        'request' => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '401200',
            ],
            'method'  => 'get'
        ],
        'response' => [
            'content'     => [
                'flows'   => [
                    'recurring' => true,
                    'iframe'    => false,
                    'emi'       => false,
                ],
            ]
        ]
    ],

    'testGetCardPaymentFlowAndIinDetailsWithHdfcDebitIin' => [
        'request' => [
            'url'     => '/payment/iin',
            'content' => [
                'iin' => '401200',
            ],
            'method'  => 'get'
        ],
        'response' => [
            'content'    => [
                'flows'   => [
                    'recurring' => true,
                    'iframe'    => false,
                    'emi'       => true,
                ],
                'issuer'  => 'HDFC',
                'type'    => 'debit',
                'network' => 'Visa',
            ]
        ]
    ],

    'testGetCardPaymentFlows' => [
        'request'  => [
            'url'     => '/payment/flows',
            'content' => [
                'card_number' => '4012001036275556',
            ],
            'method'  => 'post',
        ],
        'response' => [
            'content' => [
                'pin' => true,
                'otp' => true,
            ],
        ],
    ],

    'testGetBulkFlows' => [
        'request' => [
            'url' => '/iins/list',
            'method' => 'GET',
            'content' => [
                'flow' => 'otp',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetInnsListWithFeatures' => [
        'request' => [
            'url' => '/iins/list',
            'method' => 'GET',
            'content' => [
                'flow' => 'otp',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetInnsListBySubtype' => [
        'request' => [
            'url' => '/iins/list',
            'method' => 'GET',
            'content' => [
                'sub_type' => 'business',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetInnsListBySubtypeNoneExisting' => [
        'request' => [
            'url' => '/iins/list',
            'method' => 'GET',
            'content' => [
                'sub_type' => 'business',
            ],
        ],
        'response' => [
            'content' => [
            ],
        ],
    ],

    'testGetInnsListInvalidSubtype'  => [
        'request' => [
            'url' => '/iins/list',
            'method' => 'GET',
            'content' => [
                'sub_type' => 'subtype_123',
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The selected sub type is invalid.',
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE
        ],
    ],

    'testBulkFlowsUpdateEnable' => [
        'request' => [
            'url'     => '/iins/bulk',
            'method'  => 'PATCH',
            'content' => [
                'iins'   => ['401200', '401201', '234567'],
                'payload' => [
                    'flows'   => [
                        'otp' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                '401200' => [
                    'flows'   => [
                        'pin',
                        'otp',
                    ],
                ],
                '401201' => [
                    'flows'   => [
                        'pin',
                        'otp',
                    ],
                ],
            ],
        ],
    ],

    'testBulkFlowsUpdateDisable' => [
        'request' => [
            'url'     => '/iins/bulk',
            'method'  => 'PATCH',
            'content' => [
                'iins'   => ['401200', '401201', '234567'],
                'payload' => [
                    'flows'   => [
                        'pin' => '0',
                    ],
                ]
            ],
        ],
        'response' => [
            'content' => [
                '401200' => [
                    'flows'   => [
                        'otp',
                    ],
                ],
                '401201' => [
                    'flows'   => [
                    ],
                ],
            ],
        ],
    ],

    'testBulkFlowsInvalidInput' => [
        'request' => [
            'url'     => '/iins/bulk',
            'method'  => 'PATCH',
            'content' => [
                'iins'   => ['840120', '401201', '2345671'],
                'payload' => [
                    'flows'   => [
                        'otp' => '0',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                'error' => [
                    'code'        => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description' => 'The IIN elements must be of 6 digit.',
                ],
            ],
            'status_code' => 400
        ],
        'exception' => [
            'class'               => 'RZP\Exception\BadRequestValidationFailureException',
            'internal_error_code' => ErrorCode::BAD_REQUEST_VALIDATION_FAILURE,
        ],
    ],

    'testBulkMandateHubUpdateEnable' => [
        'request' => [
            'url'     => '/iins/bulk',
            'method'  => 'PATCH',
            'content' => [
                'iins'   => ['401200', '401201'],
                'payload' => [
                    'mandate_hubs' => [
                        'mandate_hq' => '1',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                '401200' => [
                    'mandate_hubs' => [
                        'mandate_hq',
                        'billdesk_sihub',
                    ],
                ],
                '401201' => [
                    'mandate_hubs' => [
                        'mandate_hq',
                        'billdesk_sihub',
                    ],
                ],
            ],
        ],
    ],

    'testBulkMandateHubUpdateDisable' => [
        'request' => [
            'url'     => '/iins/bulk',
            'method'  => 'PATCH',
            'content' => [
                'iins'   => ['401200', '401201'],
                'payload' => [
                    'mandate_hubs' => [
                        'billdesk_sihub' => '0',
                    ],
                ],
            ],
        ],
        'response' => [
            'content' => [
                '401200' => [
                    'mandate_hubs' => [],
                ],
                '401201' => [
                    'mandate_hubs' => [
                        'mandate_hq',
                    ],
                ],
            ],
        ],
    ],

    'testIinsBulkUpdate' => [
        'request' => [
            'url'     => '/iins/bulk',
            'method'  => 'PATCH',
            'content' => [
                'iins'   => ['401200', '401201'],
                'payload' => [
                    'flows'   => [
                        'magic' => '1',
                    ],
                    'country'        => 'IN',
                    'emi'            => 1,
                    'network'        => 'Visa',
                    'type'           => 'credit',
                ],
            ],
        ],
        'response' => [
            'content' => [
                '401200' => [
                    'flows'   => [
                        '3ds',
                        'magic',
                    ],
                    'country'        => 'IN',
                    'emi'            => true,
                    'network'        => 'Visa',
                    'type'           => 'credit',
                ],
                '401201' => [
                    'flows'   => [
                        '3ds',
                        'magic',
                    ],
                    'country'        => 'IN',
                    'emi'            => true,
                    'network'        => 'Visa',
                    'type'           => 'credit',
                ],
            ],
        ],
    ],

    'testDisableMultipleIinFlows' => [
        'request' => [
            'url'     => '/cardps/iins/disable',
            'method'  => 'POST',
            'content' => [
                'iin'   => '112333',
                'flows' => ['headless_otp']
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => '112333',
                'flows'     => ['ivr'],
            ],
        ],
    ],
];
