<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingJsbCombinedFile' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['jsb'],
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
                        'target'              => 'jsb',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ],
    ]
];
