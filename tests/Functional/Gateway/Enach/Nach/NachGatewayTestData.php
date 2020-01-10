<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGatewayFileRegister' => [
        'request' => [
            'content' => [
                'type'    => 'nach_register',
                'targets' => ['paper_nach_citi'],
                'begin'   => Carbon::yesterday(Timezone::IST)->getTimestamp() + 1000,
                'end'     => Carbon::now(Timezone::IST)->getTimestamp() + 1000,
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
                'begin'   => Carbon::yesterday(Timezone::IST)->getTimestamp() + 1000,
                'end'     => Carbon::now(Timezone::IST)->getTimestamp() + 1000,
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
];