<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateKotakCombinedFileForNonTpv' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'targets' => ['kotak'],
                'begin'    => Carbon::today(Timezone::IST)->timestamp,
                'end'      => Carbon::tomorrow(Timezone::IST)->timestamp
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
                        'target'              => 'kotak',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ],
        ],
    ],

    'testGenerateKotakCombinedFileForTpv' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'targets' => ['kotak'],
                'begin'   => Carbon::today(Timezone::IST)->timestamp,
                'end'     => Carbon::tomorrow(Timezone::IST)->timestamp,
                'tpv'     => '1'
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
                        'target'              => 'kotak',
                        'entity'              => 'gateway_file',
                        'tpv'                 => true,
                        'admin'               => true
                    ],
                ],
            ],
        ],
    ]
];
