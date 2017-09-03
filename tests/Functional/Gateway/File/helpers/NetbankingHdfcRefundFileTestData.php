<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingHdfcRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'source'  => 'hdfc',
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
                'type'                => 'refund',
                'source'              => 'hdfc',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ]
];
