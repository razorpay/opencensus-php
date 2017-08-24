<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testGenerateCombinedFile' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'gateway' => 'netbanking_rbl',
                'bank'    => 'RATN',
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
                'gateway'             => 'netbanking_rbl',
                'bank'                => 'RATN',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ],
    ]
];
