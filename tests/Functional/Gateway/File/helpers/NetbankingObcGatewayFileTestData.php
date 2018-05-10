<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateCombinedFileTest' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
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
                        'status'              => 'file_sent',
                        'sender'              => 'refunds@razorpay.com',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'type'                => 'combined',
                        'target'              => 'obc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ],
    ]
];
