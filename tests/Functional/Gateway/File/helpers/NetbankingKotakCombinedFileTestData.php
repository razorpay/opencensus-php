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
    ],

    'testGenerateKotakCombinedFileForNonTpvRefundsOutOfRange' => [
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
                        'status'              => 'failed',
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

    'testGenerateKotakCombinedFileForTpvRefundsOutOfRange' => [
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
                        'status'              => 'failed',
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
    ],

    'testGenerateTpvKotakRefundFile' => [
        'request' => [
            'content' => [
                'bank' => "KKBK",
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
                'netbanking_kotak' =>
                    [
                        'refunds' => [
                            'tpv' => storage_path('files/filestore/Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OTRAZORPAY_test_'.Carbon::today(Timezone::IST)->format('d-m-Y').'.txt'),
                            'nonTpv' => ''
                        ],
                        'claims' =>
                            ['tpv' => storage_path('files/filestore/Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OTRAZORPAY_test_'.Carbon::today(Timezone::IST)->format('d-m-Y').'.txt'),
                                'nonTpv' => ''
                    ]
                ]
            ],
        ],
    ],

    'testGenerateNonTpvKotakRefundFile' => [
        'request' => [
            'content' => [
                'bank' => "KKBK",
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
                'netbanking_kotak' =>
                    [
                        'refunds' => [
                            'tpv' => '',
                            'nonTpv' => storage_path('files/filestore/Kotak/Refund/Netbanking/Kotak_Netbanking_Refund_OSRAZORPAY_test_'.Carbon::today(Timezone::IST)->format('d-m-Y').'.txt'),
                        ],
                        'claims' =>
                            ['tpv' => '',
                                'nonTpv' => storage_path('files/filestore/Kotak/Claims/Netbanking/Kotak_Netbanking_Claim_OSRAZORPAY_test_'.Carbon::today(Timezone::IST)->format('d-m-Y').'.txt'),
                            ]
                    ]
            ],
        ],
    ]
];
