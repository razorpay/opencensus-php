<?php

namespace RZP\Tests\Functional\Subscription;

return [
    'testCreatePlan' => [
        'request' => [
            'url' => '/plans',
            'method' => 'post',
            'content' => [
                'amount'            => 2000,
                'currency'          => 'INR',
                'period'            => 'monthly',
                'interval'          => 2,
                'name'              => 'test plan',
            ],
        ],
        'response' => [
            'content' => [
                'amount' =>  2000,
                'currency' => 'INR',
                'period' => 'monthly',
                'interval' => 2,
                'name' => 'test plan',
                'notes' => [],
            ],
        ],
    ],

    'testCreateSubscription' => [
        'request' => [
            'url' => '/plans/plan_1000000000plan/subscriptions',
            'method' => 'post',
            'content' => [
                'customer_id'   => 'cust_100000customer',
                'quantity'      => 1,
            ],
        ],
        'response' => [
            'content' => [
                'customer_id' => 'cust_100000customer',
                'status' => 'created',
                'current_start' => null,
                'current_end' => null,
                'ended_at' => null,
                'quantity' => 1,
                'token_id' => null,
                'notes' => [],
                'total_count' => 1,
                'paid_count' => 0
            ],
        ],
    ],

    'testSubscriptionCharge' => [
        'request' => [
            'url' => '/subscriptions/charge',
            'method' => 'post',
            'content' => [],
        ],
        'response' => [
            'content' => []
        ],
    ],
];
