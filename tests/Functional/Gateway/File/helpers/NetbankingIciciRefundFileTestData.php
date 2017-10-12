<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingIciciRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['icici'],
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
                        'type'                => 'refund',
                        'target'              => 'icici',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ],
        ],
    ],
];
