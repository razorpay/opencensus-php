<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testNetbankingAusfCorpCombinedFile' => [
        'request' => [
            'content' => [
                'type'    => 'combined',
                'targets' => ['aubl_corp'],
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
                        'target'              => 'aubl_corp',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ],
        ],
    ],
];
