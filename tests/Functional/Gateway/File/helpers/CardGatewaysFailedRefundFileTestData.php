<?php

use Carbon\Carbon;

use RZP\Constants\Timezone;

return [
    'testAmexFailedRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund_failed',
                'targets' => ['amex'],
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
                        'target'              => 'amex',
                        'entity'              => 'gateway_file',
                        'admin'               => true,
                    ],
                ],
            ],
        ],
    ],

    'testHdfcFailedRefundFile' => [
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
                        'status'              => 'acknowledged',
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
