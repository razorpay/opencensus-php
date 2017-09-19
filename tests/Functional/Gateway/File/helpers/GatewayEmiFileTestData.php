<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateEmiFile' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileWithNoEmiPayments' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'acknowledged',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'comments'            => 'Valid data not available for file processing',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileWithFileGenerationError' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'failed',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'error_code'          => 'error_generating_file',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ],

    'testGenerateEmiFileWithMailSendError' => [
        'request' => [
            'content' => [
                'type'    => 'emi',
                'targets' => ['axis'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'failed',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emifiles@razorpay.com',
                        'type'                => 'emi',
                        'target'              => 'axis',
                        'error_code'          => 'error_sending_file',
                        'entity'              => 'gateway_file',
                    ]
                ]
            ]
        ]
    ]
];
