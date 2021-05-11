<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingHdfcRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'targets' => ['hdfc'],
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
                        'target'              => 'hdfc',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'testGenerateNetbankingHdfcRefundFile' => [
        'request' => [
            'content' => [
                'bank' => 'HDFC',
                'method' => 'netbanking',
                'mode' => 'test',
                'from' => Carbon::today(Timezone::IST)->getTimestamp(),
                'to'  => Carbon::tomorrow(Timezone::IST)->getTimestamp()
            ],
            'url' => '/refunds/excel',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                    'netbanking_hdfc' => [
                        'file' => storage_path('files/filestore/Hdfc/Refund/Netbanking/HDFC_Netbanking_Refunds_test_'.Carbon::today(Timezone::IST)->format('d-m-Y').'.xlsx'),
                        'count' => 1
                    ]
            ]
        ]
    ],
];
