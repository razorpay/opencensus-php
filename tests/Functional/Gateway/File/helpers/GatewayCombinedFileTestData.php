<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateCombinedFile' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'source'  => 'axis',
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
                'source'              => 'axis',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ],

    'testGenerateCombinedFileWithNoRefundOrClaims' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'source'  => 'axis',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'no_data_for_file_generation',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'combined',
                'source'              => 'axis',
                'entity'              => 'gateway_file',
                'admin'               => true
            ],
        ],
    ],

    'testGenerateCombinedFileWithClaimsLessThanRefunds' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'source'  => 'axis',
                'from'    => Carbon::today(Timezone::IST)->timestamp,
                'to'      => Carbon::tomorrow(Timezone::IST)->timestamp
            ],
            'url' => '/gateway/files',
            'method' => 'POST'
        ],
        'response' => [
            'content' => [
                'status'              => 'failed',
                'failure_code'        => 'claim_amount_less_than_refund_amount',
                'scheduled'           => true,
                'partially_processed' => false,
                'attempts'            => 1,
                'sender'              => 'refunds@razorpay.com',
                'type'                => 'combined',
                'source'              => 'axis',
                'entity'              => 'gateway_file',
                'admin'               => true
            ],
        ],
    ]
];
