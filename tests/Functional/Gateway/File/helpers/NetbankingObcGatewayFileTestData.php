<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['obc'],
                'begin'    => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'      => Carbon::tomorrow(Timezone::IST)->getTimestamp()
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
                        'status'              => 'file_generated',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'refund',
                        'target'              => 'obc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ],
    ]
];
