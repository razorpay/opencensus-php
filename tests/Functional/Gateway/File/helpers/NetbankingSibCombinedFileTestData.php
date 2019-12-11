<?php

use Carbon\Carbon;

use RZP\Constants\Timezone;

return [
    'testNetbankingSibCombinedFile' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['sib'],
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
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'refunds@razorpay.com',
                        'type'                => 'combined',
                        'target'              => 'sib',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ]
    ],
    'testNetbankingSibCombinedFileOnScroogeGolive' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['sib'],
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
                        'target'              => 'sib',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ]
            ]
        ]
    ]
];
