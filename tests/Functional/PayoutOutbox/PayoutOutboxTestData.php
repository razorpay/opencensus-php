<?php

return [
    'testPayoutOutboxPartitionCron'     =>  [
        'request' => [
            'url' => '/payout_outbox/partition',
            'method' => 'post',
            'content' => [
            ],
        ],
        'response' => [
            'content' => [
                'success' => true
            ],
        ],
    ],
];
