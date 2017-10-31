<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateKotakCombinedFileForNonTpv' => [
        'request' => [
            'content' => [
                'type'     => 'combined',
                'targets'  => ['kotak'],
                'sub_type' => 'non_tpv',
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
                        'sub_type'            => 'non_tpv',
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
                'type'     => 'combined',
                'targets'  => ['kotak'],
                'sub_type' => 'tpv',
                'begin'    => Carbon::today(Timezone::IST)->timestamp,
                'end'      => Carbon::tomorrow(Timezone::IST)->timestamp,
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
                        'sub_type'            => 'tpv',
                        'target'              => 'kotak',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ],
        ],
    ]
];
