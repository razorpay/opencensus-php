<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateCombinedFile' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'targets' => ['federal'],
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
                        'target'              => 'federal',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ],
    ],

    'testGenerateFederalRefundFile' => [
        'request' => [
            'content' => [
                'bank' => "FDRL",
                'method' => "netbanking",
                'mode' => "test",
                'from'=> Carbon::today(Timezone::IST)->getTimestamp(),
                'to'  => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
            'url' => '/refunds/excel',
            'method' => 'POST'
        ],
        'response' => [
            'content'=> [
                'netbanking_federal' => [
                    'refunds' => storage_path('files/filestore/FBK_REFUND_'.Carbon::today(Timezone::IST)->format('d_m_Y').'.txt'),
                ],
            ],
        ],
    ],
];
