<?php

namespace Users\sauravchowdhury\razorpay_code\api\tests\Functional\Gateway\File\helpers;

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingAxisCombinedFile' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'gateway' => 'netbanking_axis',
                'bank'    => 'UTIB',
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
                'gateway'             => 'netbanking_axis',
                'bank'                => 'UTIB',
                'entity'              => 'gateway_file',
                'admin'               => true
            ]
        ]
    ]
];
