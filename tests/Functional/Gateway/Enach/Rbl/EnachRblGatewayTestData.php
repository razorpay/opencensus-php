<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testDebitFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['enach_rbl'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
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
                            'settlements@razorpay.com'
                        ],
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],
];