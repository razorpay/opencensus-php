<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testFile'  => [
        'request' => [
            'content' => [
                'type'    => 'paresdata',
                'targets' => ['first_data'],
                'begin'   => Carbon::yesterday(Timezone::IST)->getTimestamp(),
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
                        'sender'              => 'pod.gateway@razorpay.com',
                        'type'                => 'paresdata',
                        'target'              => 'first_data',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ]
                ]
            ]
        ]
    ]
];
