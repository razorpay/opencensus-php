<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingIciciRefundFile' => [
        'request' => [
            'content' => [
                'type'    => 'refund',
                'gateway' => 'netbanking_icici',
                'bank'    => 'ICIC',
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
                'gateway'             => 'netbanking_icici',
                'bank'                => 'ICIC',
                'entity'              => 'gateway_file',
                'admin'               => true
            ],
        ],
    ],
];
