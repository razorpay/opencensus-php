<?php

use Carbon\Carbon;

use RZP\Error\PublicErrorCode;
use RZP\Constants\Timezone;

return [
    'testAuthenticationFailed' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::GATEWAY_ERROR,
                    'description'   => 'Payment processing failed due to error at bank or wallet gateway'
                ],
            ],
            'status_code' => 502,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\GatewayErrorException::class,
            'internal_error_code'   => 'GATEWAY_ERROR_MANDATE_CREATION_FAILED',
        ],
    ],

    'testRegistrationReconWithTestMerchantProxyAuth' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Invalid type passed for batch creation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testRegistrationReconWithSharedMerchantProxyAuth' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code'          => PublicErrorCode::BAD_REQUEST_ERROR,
                    'description'   => 'Invalid type passed for batch creation'
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'                 => \RZP\Exception\BadRequestValidationFailureException::class,
            'internal_error_code'   => 'BAD_REQUEST_VALIDATION_FAILURE',
        ],
    ],

    'testDebitFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['enach_rbl'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
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
                        'recipients'          => [
                            'rbl.emandate@razorpay.com'
                        ],
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testRegisterFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_register',
                'targets' => ['enach_rbl'],
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
                        'type'                => 'emandate_register',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'tokenWebhookData' => [
        'mode'  => 'test',
        'event' => [
            'entity'   => 'event',
            'event' => 'token.confirmed',
            'contains' => [
                'token',
            ],
            'payload'  => [
                'token' => [
                    'entity' => [
                        'recurring' => true,
                        'recurring_details' => [
                            'status' => 'confirmed'
                        ]
                    ]
                ],
            ],
        ],
    ]
];
