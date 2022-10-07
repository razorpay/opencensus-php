<?php

use Carbon\Carbon;

use RZP\Constants\Timezone;

return [
    'testNetbankingKarbCombinedFile' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'targets' => ['karb'],
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
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'combined',
                        'target'              => 'karb',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ],
        ],
    ],
];
