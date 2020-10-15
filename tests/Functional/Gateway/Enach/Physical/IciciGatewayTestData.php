<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGatewayFileRegister' => [
        'request' => [
            'content' => [
                'type'    => 'nach_register',
                'targets' => ['paper_nach_icici'],
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
                        'target'              => 'paper_nach_icici',
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
                'targets' => ['paper_nach_icici'],
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
                        'type'                => 'nach_register',
                        'target'              => 'paper_nach_icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                        'comments'            => 'No data present for gateway file processing in the given time period'
                    ],
                ],
            ]
        ],
    ],

    'testGatewayFileRegisterForPaymentCreatedOnNonWorkingDay' => [
        'request' => [
            'content' => [
                'type'    => 'nach_register',
                'targets' => ['paper_nach_icici'],
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
                        'target'              => 'paper_nach_icici',
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
                'targets' => ['combined_nach_icici'],
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
                        'target'              => 'combined_nach_icici',
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
                'targets' => ['combined_nach_icici'],
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
                        'target'              => 'combined_nach_icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                        'comments'            => 'No data present for gateway file processing in the given time period'
                    ],
                ],
            ]
        ],
    ],
];
