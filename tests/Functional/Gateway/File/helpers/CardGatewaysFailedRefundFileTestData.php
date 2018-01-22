<?php

use Carbon\Carbon;

use RZP\Constants\Timezone;

return [
    'testAxisMigsFailedRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['axis_migs'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
            ],
            'url' => '/gateway/files',
            'method' => 'POST',
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
                        'type'                => 'refund_failed',
                        'target'              => 'axis_migs',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ],
        ],
    ],

    'testFirstDatadRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['icic_first_data'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
            ],
            'url' => '/gateway/files',
            'method' => 'POST',
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
                        'type'                => 'refund_failed',
                        'target'              => 'icic_first_data',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ],
        ],
    ],

    'testCybersourcedRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['cybersource'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
            ],
            'url' => '/gateway/files',
            'method' => 'POST',
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
                        'type'                => 'refund_failed',
                        'target'              => 'cybersource',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ],
        ],
    ],

    'testHdfcFaileddRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['hdfc'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
            ],
            'url' => '/gateway/files',
            'method' => 'POST',
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
                        'type'                => 'refund_failed',
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ],
        ],
    ],
];
