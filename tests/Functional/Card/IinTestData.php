<?php

use RZP\Gateway\Hdfc;
use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;
use RZP\Error\PublicErrorDescription;

use RZP\Tests\Functional\Fixtures\Entity\Iin;
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
                'iin'       => 112333,
                'network'   => 'RuPay',
                'type'      => 'debit',
                'recurring' => false,
            ],
        ],
    ],

    'testAddIinWithRecurring' => [
        'request' => [
            'url' => '/iins',
            'method' => 'post',
            'content' => [
                'iin'       => 112333,
                'network'   => 'RuPay',
                'type'      => 'debit',
                'recurring' => 1,
            ],
        ],
        'response' => [
            'content' => [
                'iin'       => 112333,
                'network'   => 'RuPay',
                'type'      => 'debit',
                'recurring' => true,
            ],
        ],
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
                'iin'            => 112333,
                'network'        => 'RuPay',
                'type'           => 'credit',
                'country'        => 'IN',
                'issuer'         => 'HDFC',
                'issuer_name'    => 'HDFC',
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
                'iin'           => 607500,
                'category'      => 'STANDARD',
                'network'       => 'RuPay',
                'type'          => 'debit',
                'country'       => 'IN',
                'issuer_name'   => 'PUNJAB NATIONAL BANK',
                'trivia'        => 'random trivia'
            ]
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
                'count' => 27,
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
];
