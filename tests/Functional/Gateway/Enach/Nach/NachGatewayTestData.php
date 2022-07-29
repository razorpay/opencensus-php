<?php

use RZP\Error\ErrorCode;
use RZP\Error\PublicErrorCode;

return [
    'testGatewayFileRegister' => [
        'request' => [
            'content' => [
                'type'    => 'nach_register',
                'targets' => ['paper_nach_citi'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_register',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFileRegisterWithIfscMapping' => [
    'request' => [
        'content' => [
            'type'    => 'nach_register',
            'targets' => ['paper_nach_citi'],
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
                        ''
                    ],
                    'status'              => 'file_generated',
                    'scheduled'           => true,
                    'partially_processed' => false,
                    'attempts'            => 1,
                    'type'                => 'nach_register',
                    'target'              => 'paper_nach_citi',
                    'entity'              => 'gateway_file',
                    'admin'               => true
                ],
            ],
        ]
    ],
],

    'testGatewayFileRegisterOnNonWorkingDay' => [
        'request' => [
            'content' => [
                'type'    => 'nach_register',
                'targets' => ['paper_nach_citi'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_register',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFileDebitForPaymentCreatedOnNonWorkingDay' => [
        'request' => [
            'content' => [
                'type'    => 'nach_register',
                'targets' => ['paper_nach_citi'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_register',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFileDebit' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFileWithEarlyPresentmentFeatureFor11AMPayment' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
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
                        'recipients'          => [''],
                        'status'              => 'acknowledged',
                        'comments'            => 'No data present for gateway file processing in the given time period',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFilePartGenerationWithEarlyPresentmentFeatureFor4AMPayment' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
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
                        'recipients'          => [''],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFileDebitOnNonWorkingDay' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['paper_nach_citi'],
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
                            ''
                        ],
                        'status'              => 'acknowledged',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'paper_nach_citi',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                        'comments'            => 'No data present for gateway file processing in the given time period'
                    ],
                ],
            ]
        ],
    ],
    'testGatewayFileEarlyDebitWithoutFeatureEnabled' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['combined_nach_citi_early_debit'],
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
                            ''
                        ],
                        'status'              => 'acknowledged',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'combined_nach_citi_early_debit',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                        'comments'            => 'No data present for gateway file processing in the given time period'
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFileEarlyDebit' => [
        'request' => [
            'content' => [
                'type'    => 'nach_debit',
                'targets' => ['combined_nach_citi_early_debit'],
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'combined_nach_citi_early_debit',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

    'process_via_batch_service' => [
        'request' => [
            'url'    => '/nach/batch_service',
            'method' => 'post',
            'server' => [
                'mode' => 'test',
            ],
        ],
        'response' => [
            'content' => [],
        ]
    ],

    'testPaymentRejectResponse' => [
        'response'  => [
            'content'     => [
                'error' => [
                    'code' => PublicErrorCode::BAD_REQUEST_ERROR,
                ],
            ],
            'status_code' => 400,
        ],
        'exception' => [
            'class'               => RZP\Exception\GatewayErrorException::class,
            'internal_error_code' => ErrorCode::BAD_REQUEST_PAYMENT_INVALID_ACCOUNT,
        ],
    ],

    'testGatewayFileForAutomation' => [
        'request' => [
            'content' => [
                'type'       => 'nach_debit',
                'targets'    => ['paper_nach_citi_v2'],
                'end'        => 9,
                'time_range' => 24,
                'sub_type'   => 0,
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
                            ''
                        ],
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'nach_debit',
                        'target'              => 'paper_nach_citi_v2',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

];
