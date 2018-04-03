<?php

use Carbon\Carbon;
use RZP\Constants\Timezone;

return [
    'testDebitFileGeneration' => [
        'request' => [
            'content' => [
                'type'    => 'emandate_debit',
                'targets' => ['enach_rbl'],
                'begin'   => Carbon::today(Timezone::IST)->getTimestamp(),
                'end'     => Carbon::tomorrow(Timezone::IST)->getTimestamp(),
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
                        'recipients'          => [
                            'rbl.emandate@razorpay.com'
                        ],
                        'status'              => 'file_sent',
                        'scheduled'           => true,
                        'partially_processed' => false,
                        'attempts'            => 1,
                        'sender'              => 'emandate@razorpay.com',
                        'type'                => 'emandate_debit',
                        'target'              => 'enach_rbl',
                        'entity'              => 'gateway_file',
                        'admin'               => true
                    ],
                ],
            ]
        ]
    ],

    'tokenWebhookData' => [
        'mode'  => 'test',
        'event' => [
            'entity'   => 'event',
            'event' => 'token.confirmed',
            'contains' => [
                'token',
            ],
            'payload'  => [
                'token' => [
                    'entity' => [
                        'recurring' => true,
                        'recurring_details' => [
                            'status' => 'confirmed'
                        ]
                    ]
                ],
            ],
        ],
    ]
];
