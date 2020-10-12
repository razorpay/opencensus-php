<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingIobCombinedFile' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['iob'],
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
                        'target'              => 'iob',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ],
    ]
];
