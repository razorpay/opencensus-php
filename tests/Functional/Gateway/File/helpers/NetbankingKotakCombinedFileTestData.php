<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateKotakCombinedFileForNonTpv' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'gateway' => 'netbanking_kotak',
                'bank'    => 'KKBK',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'combined',
                'gateway'             => 'netbanking_kotak',
                'bank'                => 'KKBK',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ],
    ],

    'testGenerateKotakCombinedFileForTpv' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'gateway' => 'netbanking_kotak',
                'bank'    => 'KKBK',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp,
                'tpv'     => '1'
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'mail_sent',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'combined',
                'gateway'             => 'netbanking_kotak',
                'bank'                => 'KKBK',
                'entity'              => 'gateway_file',
                'tpv'                 => true,
                'admin'               => true
            ]
        ],
    ]
];
