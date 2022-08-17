<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testFileCreatedSuccessfully' => [
        'request' => [
            'content' => [
                'type'    => 'capture',
                'targets' => ['axis_paysecure'],
                'begin'   => Carbon::yesterday()->getTimestamp(),
                'end'     => Carbon::tomorrow()->getTimestamp()
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
                        'sender'              => 'capturefiles@razorpay.com',
                        'type'                => 'capture',
                        'target'              => 'axis_paysecure',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testFileCreatedSuccessfullyWithFullRefund' => [
        'request' => [
            'content' => [
                'type'    => 'capture',
                'targets' => ['axis_paysecure'],
                'begin'   => Carbon::yesterday()->getTimestamp(),
                'end'     => Carbon::tomorrow()->getTimestamp()
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
                        'sender'              => 'capturefiles@razorpay.com',
                        'type'                => 'capture',
                        'target'              => 'axis_paysecure',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testFileCreatedSuccessfullyWithPartialRefund' => [
        'request' => [
            'content' => [
                'type'    => 'capture',
                'targets' => ['axis_paysecure'],
                'begin'   => Carbon::yesterday()->getTimestamp(),
                'end'     => Carbon::tomorrow()->getTimestamp()
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
                        'sender'              => 'capturefiles@razorpay.com',
                        'type'                => 'capture',
                        'target'              => 'axis_paysecure',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],
];
